<?php declare(strict_types=1);
/*
 * This file is part of phpunit/php-code-coverage.
 *
 * (c) Sebastian Bergmann <sebastian@phpunit.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace SebastianBergmann\CodeCoverage\Report\Html;

use const ENT_COMPAT;
use const ENT_HTML401;
use const ENT_HTML5;
use const ENT_QUOTES;
use const ENT_SUBSTITUTE;
use const JSON_THROW_ON_ERROR;
use function count;
use function htmlspecialchars;
use function json_encode;
use function round;
use function rtrim;
use function sprintf;
use function str_repeat;
use function strtolower;
use function substr_count;
use RoundingMode;
use SebastianBergmann\CodeCoverage\Data\ProcessedFunctionType;
use SebastianBergmann\CodeCoverage\Data\ProcessedMethodType;
use SebastianBergmann\CodeCoverage\Node\AbstractNode;
use SebastianBergmann\CodeCoverage\Node\Directory as DirectoryNode;
use SebastianBergmann\CodeCoverage\Node\File as FileNode;
use SebastianBergmann\CodeCoverage\Report\Html\ClassView\Node\ClassNode;
use SebastianBergmann\CodeCoverage\Report\Thresholds;
use SebastianBergmann\CodeCoverage\Test\TestSizes;
use SebastianBergmann\CodeCoverage\Version;
use SebastianBergmann\Environment\Runtime;
use SebastianBergmann\Template\Template;

/**
 * @internal This class is not covered by the backward compatibility promise for phpunit/php-code-coverage
 *
 * @no-named-arguments Parameter names are not covered by the backward compatibility promise for phpunit/php-code-coverage
 *
 * @phpstan-type CoverageItemData array{
 *     name: string,
 *     numClasses?: int,
 *     numTestedClasses?: int,
 *     testedClassesPercent?: float,
 *     testedClassesPercentAsString?: string,
 *     numMethods: int,
 *     numTestedMethods: int,
 *     testedMethodsPercent: float,
 *     testedMethodsPercentAsString: string,
 *     numExecutableLines: int,
 *     numExecutedLines: int,
 *     linesExecutedPercent: float,
 *     linesExecutedPercentAsString: string,
 *     numExecutableBranches: int,
 *     numExecutedBranches: int,
 *     branchesExecutedPercent: float,
 *     branchesExecutedPercentAsString: string,
 *     numExecutablePaths: int,
 *     numExecutedPaths: int,
 *     pathsExecutedPercent: float,
 *     pathsExecutedPercentAsString: string,
 *     numFilesWithoutBranchCoverageData?: int,
 *     icon?: string,
 *     crap?: int|string,
 *     coverageDataJson?: string,
 * }
 * @phpstan-type CoverageMetric array{level: string, percent: string, number: string, bar: string}
 *
 * @phpstan-import-type TestDataType from \SebastianBergmann\CodeCoverage\Node\Builder
 * @phpstan-import-type TestIndexType from \SebastianBergmann\CodeCoverage\Data\ProcessedCodeCoverageData
 */
abstract class Renderer
{
    protected const int HTML_SPECIAL_CHARS_FLAGS = ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE;

    /**
     * Maps each combination of test sizes to the key suffix used in the
     * coverage data JSON that drives the test size filter.
     *
     * @var array<int, non-empty-string>
     */
    protected const array TEST_SIZE_JSON_KEY_SUFFIXES = [
        TestSizes::SMALL                                        => 'Small',
        TestSizes::MEDIUM                                       => 'Medium',
        TestSizes::LARGE                                        => 'Large',
        TestSizes::SMALL | TestSizes::MEDIUM                    => 'SM',
        TestSizes::SMALL | TestSizes::LARGE                     => 'SL',
        TestSizes::MEDIUM | TestSizes::LARGE                    => 'ML',
        TestSizes::SMALL | TestSizes::MEDIUM | TestSizes::LARGE => 'SML',
    ];

    /**
     * The individual test sizes the test size filter can offer, in the order
     * in which they are offered.
     *
     * @var array<int, non-empty-string>
     */
    private const array TEST_SIZE_LABELS = [
        TestSizes::SMALL  => 'Small',
        TestSizes::MEDIUM => 'Medium',
        TestSizes::LARGE  => 'Large',
    ];
    protected readonly SyntaxHighlighter $syntaxHighlighter;
    protected string $templatePath;
    protected string $generator;
    protected string $date;
    protected Thresholds $thresholds;
    protected bool $hasBranchCoverage;
    protected bool $hasPathCoverage;

    /**
     * The test sizes for which the report has coverage data.
     *
     * @var int<0, 7>
     */
    protected int $testSizes;
    protected Views $views;
    protected string $version;

    /**
     * @var array<string, string>
     */
    private array $fileToClassMap = [];

    /**
     * @var array<non-empty-string, Template>
     */
    private array $templates = [];

    /**
     * @var array<TestIndexType, string>
     */
    private array $popoverContentForTest = [];

    /**
     * @param int<0, 7> $testSizes
     */
    public function __construct(string $templatePath, string $generator, string $date, Thresholds $thresholds, bool $hasBranchCoverage, bool $hasPathCoverage, int $testSizes = TestSizes::ALL, Views $views = Views::FileViewAndClassView)
    {
        $this->templatePath      = $templatePath;
        $this->generator         = $generator;
        $this->date              = $date;
        $this->thresholds        = $thresholds;
        $this->version           = Version::id();
        $this->hasBranchCoverage = $hasBranchCoverage;
        $this->hasPathCoverage   = $hasPathCoverage;
        $this->testSizes         = $testSizes;
        $this->views             = $views;
        $this->syntaxHighlighter = new SyntaxHighlighter;
    }

    /**
     * @param array<string, string> $map
     */
    public function setFileToClassMap(array $map): void
    {
        $this->fileToClassMap = $map;
    }

    /**
     * The text of a template does not change while a report is rendered, so the
     * same instance is used again instead of reading and parsing the same
     * template file for every node, source line, and coverage bar.
     *
     * An instance returned by this method still carries the values of its
     * previous use: only use it for templates that have all of their
     * placeholders set on every use.
     *
     * @param non-empty-string $name
     */
    protected function template(string $name): Template
    {
        return $this->templates[$name] ??= new Template($name, '{{', '}}');
    }

    /**
     * @return non-empty-string
     */
    protected function templateNameForTier(string $base): string
    {
        if ($this->hasPathCoverage) {
            return $this->templatePath . $base . '_branch_and_path.html';
        }

        if ($this->hasBranchCoverage) {
            return $this->templatePath . $base . '_branch.html';
        }

        return $this->templatePath . $base . '.html';
    }

    /**
     * @param CoverageItemData $data
     */
    protected function renderItemTemplate(Template $template, array $data): string
    {
        $metrics = $this->metrics($data);

        $template->setVar(
            [
                'icon'                      => $data['icon'] ?? '',
                'crap'                      => (string) ($data['crap'] ?? ''),
                'name'                      => $data['name'],
                'coverage_data'             => htmlspecialchars($data['coverageDataJson'] ?? '{}', ENT_COMPAT),
                'lines_bar'                 => $metrics['lines']['bar'],
                'lines_executed_percent'    => $metrics['lines']['percent'],
                'lines_level'               => $metrics['lines']['level'],
                'lines_number'              => $metrics['lines']['number'],
                'paths_bar'                 => $metrics['paths']['bar'],
                'paths_executed_percent'    => $metrics['paths']['percent'],
                'paths_level'               => $metrics['paths']['level'],
                'paths_number'              => $metrics['paths']['number'],
                'branches_bar'              => $metrics['branches']['bar'],
                'branches_executed_percent' => $metrics['branches']['percent'],
                'branches_level'            => $metrics['branches']['level'],
                'branches_number'           => $metrics['branches']['number'],
                'methods_bar'               => $metrics['methods']['bar'],
                'methods_tested_percent'    => $metrics['methods']['percent'],
                'methods_level'             => $metrics['methods']['level'],
                'methods_number'            => $metrics['methods']['number'],
                'classes_bar'               => $metrics['classes']['bar'],
                'classes_tested_percent'    => $metrics['classes']['percent'],
                'classes_level'             => $metrics['classes']['level'],
                'classes_number'            => $metrics['classes']['number'],
            ],
        );

        return $template->render();
    }

    /**
     * Renders the headline figures shown above a coverage table.
     *
     * @param CoverageItemData $data
     */
    protected function renderSummary(array $data, string $methodsLabel, string $classesLabel): string
    {
        $labels = ['lines' => 'Lines'];

        if ($this->hasBranchCoverage) {
            $labels['branches'] = 'Branches';
        }

        if ($this->hasPathCoverage) {
            $labels['paths'] = 'Paths';
        }

        $labels['methods'] = $methodsLabel;
        $labels['classes'] = $classesLabel;

        $metrics  = $this->metrics($data);
        $template = $this->template($this->templatePath . 'summary_metric.html');
        $rendered = '';

        foreach ($labels as $group => $label) {
            $template->setVar(
                [
                    'group'   => $group,
                    'label'   => $label,
                    'level'   => $metrics[$group]['level'],
                    'percent' => $metrics[$group]['percent'],
                    'number'  => $metrics[$group]['number'],
                    'bar'     => $metrics[$group]['bar'],
                ],
            );

            $rendered .= $template->render();
        }

        $summary = $this->template($this->templatePath . 'summary.html');

        $summary->setVar(
            [
                'coverage_data' => htmlspecialchars($data['coverageDataJson'] ?? '{}', ENT_COMPAT),
                'metrics'       => $rendered,
            ],
        );

        return $summary->render();
    }

    protected function setCommonTemplateVariables(Template $template, AbstractNode $node): void
    {
        $pathToRoot = $this->pathToRoot($node);

        $template->setVar(
            [
                'id'               => $node->id(),
                'full_path'        => $this->escapeHtml($node->pathAsString()),
                'path_to_root'     => $pathToRoot,
                'breadcrumbs'      => $this->breadcrumbs($node),
                'date'             => $this->date,
                'version'          => $this->version,
                'runtime'          => $this->runtimeString(),
                'generator'        => $this->generator,
                'test_size_filter' => $this->testSizeFilter(),
                'view_switcher'    => $this->views->classView() ? $this->viewSwitcher($pathToRoot, 'files', 'index.html', $this->classViewTarget($node)) : '',
            ],
        );
    }

    /**
     * The toolbar that filters the coverage metrics by test size.
     *
     * A test size for which the report has no coverage data is offered, but
     * disabled: filtering by it could only ever result in 0%, and showing it
     * as unavailable tells the difference between "no test of this size
     * covers any code" and "tests of this size cover nothing", which an
     * enabled checkbox that results in 0% does not. When no test size at all
     * has coverage data, the toolbar is not rendered because there is nothing
     * it could filter by.
     */
    protected function testSizeFilter(): string
    {
        if ($this->testSizes === 0) {
            return '';
        }

        $checkboxes = '';

        foreach (self::TEST_SIZE_LABELS as $testSize => $label) {
            $id = strtolower($label);

            // the checkbox itself is visually hidden, so the explanation has
            // to be on the label that is styled to look like a button
            $disabled = '';
            $title    = '';

            if (($this->testSizes & $testSize) !== $testSize) {
                $disabled = ' disabled';
                $title    = sprintf(' title="No code in this report is covered by tests of size %s"', $id);
            }

            $checkboxes .= sprintf(
                '     <input type="checkbox" id="filter-size-%s" data-test-size-filter="%s" autocomplete="off"%s><label for="filter-size-%s"%s>%s</label>' . "\n",
                $id,
                $id,
                $disabled,
                $id,
                $title,
                $label,
            );
        }

        return sprintf(
            '   <div class="toolbar">' . "\n" .
            '    <div class="test-size-filter" role="group" aria-label="Show coverage by test size" data-low-upper-bound="%s" data-high-lower-bound="%s">' . "\n" .
            '     <span class="control-label">Covered by tests of size</span>' . "\n" .
            '%s' .
            '     <button type="button" data-test-size-filter-all aria-pressed="true">Any</button>' . "\n" .
            '    </div>' . "\n" .
            '   </div>' . "\n",
            $this->thresholds->lowUpperBound(),
            $this->thresholds->highLowerBound(),
            $checkboxes,
        );
    }

    /**
     * Target of the "Classes" tab, relative to the root of the report.
     */
    protected function classViewTarget(AbstractNode $node): string
    {
        if ($node instanceof FileNode && isset($this->fileToClassMap[$node->id()])) {
            return $this->fileToClassMap[$node->id()];
        }

        return '_classes/index.html';
    }

    protected function escapeHtml(string $value): string
    {
        return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML5);
    }

    protected function viewSwitcher(string $pathToRoot, string $activeView, string $filesTarget = 'index.html', string $classesTarget = '_classes/index.html'): string
    {
        if ($activeView === 'files') {
            return sprintf(
                '   <ul class="tabs">' . "\n" .
                '    <li><a href="%sindex.html" aria-current="page">Files</a></li>' . "\n" .
                '    <li><a href="%s%s">Classes</a></li>' . "\n" .
                '   </ul>' . "\n",
                $pathToRoot,
                $pathToRoot,
                $classesTarget,
            );
        }

        return sprintf(
            '   <ul class="tabs">' . "\n" .
            '    <li><a href="%s%s">Files</a></li>' . "\n" .
            '    <li><a href="%s_classes/index.html" aria-current="page">Classes</a></li>' . "\n" .
            '   </ul>' . "\n",
            $pathToRoot,
            $filesTarget,
            $pathToRoot,
        );
    }

    protected function breadcrumbs(AbstractNode $node): string
    {
        $breadcrumbs = '';
        $path        = $node->pathAsArray();
        $depth       = count($path);

        if ($node instanceof FileNode) {
            $depth--;
        }

        foreach ($path as $step) {
            if ($step !== $node) {
                $depth--;

                $breadcrumbs .= $this->inactiveBreadcrumb(
                    $step,
                    str_repeat('../', $depth),
                );
            } else {
                $breadcrumbs .= $this->activeBreadcrumb($step);
            }
        }

        return $breadcrumbs;
    }

    protected function activeBreadcrumb(AbstractNode $node): string
    {
        $buffer = sprintf(
            '     <li class="current">%s</li>' . "\n",
            $this->escapeHtml($node->name()),
        );

        if ($node instanceof DirectoryNode) {
            $buffer .= '     <li class="secondary"><a href="dashboard.html">Dashboard</a></li>' . "\n";
        }

        return $buffer;
    }

    protected function inactiveBreadcrumb(AbstractNode $node, string $pathToRoot): string
    {
        return sprintf(
            '     <li><a href="%sindex.html">%s</a></li>' . "\n",
            $pathToRoot,
            $this->escapeHtml($node->name()),
        );
    }

    protected function pathToRoot(AbstractNode $node): string
    {
        $id    = $node->id();
        $depth = substr_count($id, '/');

        if ($id !== 'index' &&
            $node instanceof DirectoryNode) {
            $depth++;
        }

        return str_repeat('../', $depth);
    }

    protected function coverageBar(float $percent): string
    {
        $template = $this->template($this->templatePath . 'coverage_bar.html');

        $template->setVar(['percent' => sprintf('%.2F', round($percent, 2, RoundingMode::TowardsZero))]);

        return rtrim($template->render());
    }

    protected function thresholdsLegend(): string
    {
        return sprintf(
            '   <ul class="legend">' . "\n" .
            '    <li class="danger"><strong>Low</strong>: 0%% to %1$d%%</li>' . "\n" .
            '    <li class="warning"><strong>Medium</strong>: %1$d%% to %2$d%%</li>' . "\n" .
            '    <li class="success"><strong>High</strong>: %2$d%% to 100%%</li>' . "\n" .
            '   </ul>' . "\n",
            $this->thresholds->lowUpperBound(),
            $this->thresholds->highLowerBound(),
        );
    }

    protected function lineCoverageLegend(): string
    {
        return '   <ul class="legend">' . "\n" .
            '    <li class="covered-by-small-tests">Covered by small (and larger) tests</li>' . "\n" .
            '    <li class="covered-by-medium-tests">Covered by medium (and large) tests</li>' . "\n" .
            '    <li class="covered-by-large-tests">Covered by large tests (and tests of unknown size)</li>' . "\n" .
            '    <li class="not-covered">Not covered</li>' . "\n" .
            '    <li class="not-coverable">Not coverable</li>' . "\n" .
            '   </ul>' . "\n";
    }

    protected function branchCoverageLegend(): string
    {
        return '   <ul class="legend">' . "\n" .
            '    <li class="success">Fully covered</li>' . "\n" .
            '    <li class="warning">Partially covered</li>' . "\n" .
            '    <li class="danger">Not covered</li>' . "\n" .
            '   </ul>' . "\n";
    }

    protected function colorLevel(float $percent): string
    {
        if ($percent <= $this->thresholds->lowUpperBound()) {
            return 'danger';
        }

        if ($percent > $this->thresholds->lowUpperBound() &&
            $percent < $this->thresholds->highLowerBound()) {
            return 'warning';
        }

        return 'success';
    }

    /**
     * @param array<string, float|int> $data
     */
    protected function buildCoverageDataJson(array $data): string
    {
        return json_encode($data, JSON_THROW_ON_ERROR);
    }

    protected function coverageDataJsonFor(AbstractNode $node): string
    {
        $data = [
            'linesTotal'   => $node->numberOfExecutableLines(),
            'linesAll'     => $node->numberOfExecutedLines(),
            'methodsTotal' => $node->numberOfFunctionsAndMethods(),
            'methodsAll'   => $node->numberOfTestedFunctionsAndMethods(),
            'classesTotal' => $node->numberOfClassesAndTraits(),
            'classesAll'   => $node->numberOfTestedClassesAndTraits(),
        ];

        foreach (self::TEST_SIZE_JSON_KEY_SUFFIXES as $combination => $suffix) {
            $data['lines' . $suffix]   = $node->numberOfExecutedLinesByTestSize($combination);
            $data['methods' . $suffix] = $node->numberOfTestedFunctionsAndMethodsByTestSize($combination);
            $data['classes' . $suffix] = $node->numberOfTestedClassesAndTraitsByTestSize($combination);
        }

        return $this->buildCoverageDataJson($data);
    }

    protected function coverageDataJsonForClassNode(ClassNode $node): string
    {
        $numMethods = $node->numberOfMethods();
        $numClasses = $numMethods > 0 ? 1 : 0;

        $data = [
            'linesTotal'   => $node->numberOfExecutableLines(),
            'linesAll'     => $node->numberOfExecutedLines(),
            'methodsTotal' => $numMethods,
            'methodsAll'   => $node->numberOfTestedMethods(),
            'classesTotal' => $numClasses,
            'classesAll'   => ($numClasses === 1 && $node->numberOfTestedMethods() === $numMethods) ? 1 : 0,
        ];

        foreach (self::TEST_SIZE_JSON_KEY_SUFFIXES as $combination => $suffix) {
            $numTestedMethodsByTestSize = $node->numberOfTestedMethodsByTestSize($combination);

            $data['lines' . $suffix]   = $node->numberOfExecutedLinesByTestSize($combination);
            $data['methods' . $suffix] = $numTestedMethodsByTestSize;
            $data['classes' . $suffix] = ($numClasses === 1 && $numTestedMethodsByTestSize === $numMethods) ? 1 : 0;
        }

        return $this->buildCoverageDataJson($data);
    }

    protected function coverageDataJsonForFunctionOrMethod(ProcessedFunctionType|ProcessedMethodType $item): string
    {
        $numMethods       = 0;
        $numTestedMethods = 0;

        if ($item->executableLines > 0) {
            $numMethods = 1;

            if ($item->executedLines === $item->executableLines) {
                $numTestedMethods = 1;
            }
        }

        $data = [
            'linesTotal'   => $item->executableLines,
            'linesAll'     => $item->executedLines,
            'methodsTotal' => $numMethods,
            'methodsAll'   => $numTestedMethods,
        ];

        foreach (self::TEST_SIZE_JSON_KEY_SUFFIXES as $combination => $suffix) {
            $numTestedMethodsByTestSize = 0;

            if ($numMethods === 1 && $item->executedLinesByTestSize[$combination] === $item->executableLines) {
                $numTestedMethodsByTestSize = 1;
            }

            $data['lines' . $suffix]   = $item->executedLinesByTestSize[$combination];
            $data['methods' . $suffix] = $numTestedMethodsByTestSize;
        }

        return $this->buildCoverageDataJson($data);
    }

    protected function renderLine(Template $template, int $lineNumber, string $lineContent, string $class, string $popover, string $anchorPrefix = '', string $coverageCount = ''): string
    {
        $template->setVar(
            [
                'anchor'        => $anchorPrefix . $lineNumber,
                'lineNumber'    => (string) $lineNumber,
                'lineContent'   => $lineContent,
                'class'         => $class === '' ? '' : sprintf(' class="%s"', $class),
                'popover'       => $popover,
                'coverageCount' => $coverageCount,
            ],
        );

        return $template->render();
    }

    /**
     * The attributes that turn a source line or a table cell into the trigger
     * of a popover that lists the tests which cover it.
     */
    protected function popoverAttributes(string $title, string $content): string
    {
        return sprintf(
            ' data-popover-title="%s" data-popover-content="%s"',
            htmlspecialchars($title, self::HTML_SPECIAL_CHARS_FLAGS),
            htmlspecialchars($content, self::HTML_SPECIAL_CHARS_FLAGS),
        );
    }

    /**
     * The list item for a test is the same everywhere that test covers a line,
     * and a test usually covers many lines, so it is only built once.
     *
     * @param TestIndexType $testIndex
     * @param TestDataType  $testData
     */
    protected function createPopoverContentForTest(int $testIndex, array $testData): string
    {
        if (isset($this->popoverContentForTest[$testIndex])) {
            return $this->popoverContentForTest[$testIndex];
        }

        $testCSS = '';

        switch ($testData['status']) {
            case 'success':
                $testCSS = match ($testData['size']) {
                    'small'  => ' class="covered-by-small-tests"',
                    'medium' => ' class="covered-by-medium-tests"',
                    // no break
                    default => ' class="covered-by-large-tests"',
                };

                break;

            case 'failure':
                $testCSS = ' class="danger"';

                break;
        }

        return $this->popoverContentForTest[$testIndex] = sprintf(
            '<li%s>%s</li>',
            $testCSS,
            htmlspecialchars($testData['name'], self::HTML_SPECIAL_CHARS_FLAGS),
        );
    }

    protected function runtimeString(): string
    {
        $runtime = new Runtime;

        return sprintf(
            '<a href="%s" target="_top">%s %s</a>',
            $runtime->getVendorUrl(),
            $runtime->getName(),
            $runtime->getVersion(),
        );
    }

    /**
     * @param CoverageItemData $data
     *
     * @return array{lines: CoverageMetric, branches: CoverageMetric, paths: CoverageMetric, methods: CoverageMetric, classes: CoverageMetric}
     */
    private function metrics(array $data): array
    {
        $incompleteBranchCoverageData = ($data['numFilesWithoutBranchCoverageData'] ?? 0) > 0;

        return [
            'lines' => $this->metric(
                $data['numExecutedLines'],
                $data['numExecutableLines'],
                $data['linesExecutedPercent'],
                $data['linesExecutedPercentAsString'],
            ),
            'branches' => $this->metric(
                $data['numExecutedBranches'],
                $data['numExecutableBranches'],
                $data['branchesExecutedPercent'],
                $data['branchesExecutedPercentAsString'],
                $incompleteBranchCoverageData,
            ),
            'paths' => $this->metric(
                $data['numExecutedPaths'],
                $data['numExecutablePaths'],
                $data['pathsExecutedPercent'],
                $data['pathsExecutedPercentAsString'],
                $incompleteBranchCoverageData,
            ),
            'methods' => $this->metric(
                $data['numTestedMethods'],
                $data['numMethods'],
                $data['testedMethodsPercent'],
                $data['testedMethodsPercentAsString'],
            ),
            'classes' => $this->metric(
                $data['numTestedClasses'] ?? 0,
                $data['numClasses'] ?? 0,
                $data['testedClassesPercent'] ?? 0.0,
                $data['testedClassesPercentAsString'] ?? 'n/a',
            ),
        ];
    }

    /**
     * @return CoverageMetric
     */
    private function metric(int $tested, int $total, float $percent, string $percentAsString, bool $incomplete = false): array
    {
        if ($total === 0) {
            return ['level' => '', 'percent' => 'n/a', 'number' => '0 / 0', 'bar' => ''];
        }

        if ($incomplete) {
            $percentAsString .= ' <abbr title="Not all files have branch and path coverage data">*</abbr>';
        }

        return [
            'level'   => $this->colorLevel($percent),
            'percent' => $percentAsString,
            'number'  => $tested . ' / ' . $total,
            'bar'     => $this->coverageBar($percent),
        ];
    }
}
