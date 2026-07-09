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
use const ENT_SUBSTITUTE;
use const JSON_THROW_ON_ERROR;
use const PHP_INT_MAX;
use function array_key_exists;
use function array_keys;
use function array_merge;
use function array_pop;
use function array_slice;
use function array_unique;
use function count;
use function explode;
use function htmlspecialchars;
use function implode;
use function json_encode;
use function max;
use function min;
use function range;
use function sprintf;
use function uasort;
use SebastianBergmann\CodeCoverage\Data\ProcessedBranchCoverageData;
use SebastianBergmann\CodeCoverage\Data\ProcessedClassType;
use SebastianBergmann\CodeCoverage\Data\ProcessedFunctionCoverageData;
use SebastianBergmann\CodeCoverage\Data\ProcessedFunctionType;
use SebastianBergmann\CodeCoverage\Data\ProcessedMethodType;
use SebastianBergmann\CodeCoverage\Data\ProcessedPathCoverageData;
use SebastianBergmann\CodeCoverage\Data\ProcessedTraitType;
use SebastianBergmann\CodeCoverage\FileCouldNotBeWrittenException;
use SebastianBergmann\CodeCoverage\Node\File as FileNode;
use SebastianBergmann\CodeCoverage\Report\Thresholds;
use SebastianBergmann\CodeCoverage\Util\Percentage;
use SebastianBergmann\Template\Exception;
use SebastianBergmann\Template\Template;

/**
 * @internal This class is not covered by the backward compatibility promise for phpunit/php-code-coverage
 *
 * @no-named-arguments Parameter names are not covered by the backward compatibility promise for phpunit/php-code-coverage
 *
 * @phpstan-import-type TestType from \SebastianBergmann\CodeCoverage\CodeCoverage
 */
final class File extends Renderer
{
    private const int HTML_SPECIAL_CHARS_FLAGS = ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE;

    /**
     * Maximum number of paths per method for which table rows, interactive
     * graph highlighting data, and graph edge classes are rendered; path
     * counts grow combinatorially and would bloat the report otherwise.
     */
    private const int MAX_RENDERED_PATHS = 100;
    private readonly SyntaxHighlighter $syntaxHighlighter;
    private ?ControlFlowGraph $controlFlowGraph = null;

    public function __construct(string $templatePath, string $generator, string $date, Thresholds $thresholds, bool $hasBranchCoverage, bool $hasPathCoverage)
    {
        parent::__construct($templatePath, $generator, $date, $thresholds, $hasBranchCoverage, $hasPathCoverage);

        $this->syntaxHighlighter = new SyntaxHighlighter;
    }

    public function render(FileNode $node, string $file): void
    {
        $template = new Template($this->templateNameForTier('file'), '{{', '}}');
        $this->setCommonTemplateVariables($template, $node);

        $template->setVar(
            [
                'items'     => $this->renderItems($node),
                'lines'     => $this->renderSourceWithLineCoverage($node),
                'legend'    => '<p><span class="legend covered-by-small-tests">Covered by small (and larger) tests</span><span class="legend covered-by-medium-tests">Covered by medium (and large) tests</span><span class="legend covered-by-large-tests">Covered by large tests (and tests of unknown size)</span><span class="legend not-covered">Not covered</span><span class="legend not-coverable">Not coverable</span></p>',
                'structure' => '',
            ],
        );

        if ($this->hasBranchCoverage) {
            $template->setVar(
                ['tabs' => $this->renderViewTabs($node->name(), 'line')],
            );
        }

        try {
            $template->renderTo($file . '.html');
        } catch (Exception $e) {
            throw new FileCouldNotBeWrittenException(
                $e->getMessage(),
                $e->getCode(),
                $e,
            );
        }

        if ($this->hasBranchCoverage) {
            $template->setVar(
                [
                    'tabs'      => $this->renderViewTabs($node->name(), 'branch'),
                    'items'     => $this->renderItems($node),
                    'lines'     => $this->renderSourceWithBranchCoverage($node),
                    'legend'    => '<p><span class="success"><strong>Fully covered</strong></span><span class="warning"><strong>Partially covered</strong></span><span class="danger"><strong>Not covered</strong></span></p>',
                    'structure' => $this->renderBranchStructure($node),
                ],
            );

            try {
                $template->renderTo($file . '_branch.html');
            } catch (Exception $e) {
                throw new FileCouldNotBeWrittenException(
                    $e->getMessage(),
                    $e->getCode(),
                    $e,
                );
            }
        }

        if ($this->hasPathCoverage) {
            $template->setVar(
                [
                    'tabs'      => $this->renderViewTabs($node->name(), 'path'),
                    'items'     => $this->renderItems($node),
                    'lines'     => $this->renderSourceWithPathCoverage($node),
                    'legend'    => '<p><span class="success"><strong>Fully covered</strong></span><span class="warning"><strong>Partially covered</strong></span><span class="danger"><strong>Not covered</strong></span></p>',
                    'structure' => $this->renderPathStructure($node),
                ],
            );

            try {
                $template->renderTo($file . '_path.html');
            } catch (Exception $e) {
                throw new FileCouldNotBeWrittenException(
                    $e->getMessage(),
                    $e->getCode(),
                    $e,
                );
            }
        }
    }

    private function renderItems(FileNode $node): string
    {
        $template = new Template($this->templateNameForTier('file_item'), '{{', '}}');

        $methodItemTemplate = new Template(
            $this->templateNameForTier('method_item'),
            '{{',
            '}}',
        );

        $items = $this->renderItemTemplate(
            $template,
            [
                'name'                            => 'Total',
                'numClasses'                      => $node->numberOfClassesAndTraits(),
                'numTestedClasses'                => $node->numberOfTestedClassesAndTraits(),
                'numMethods'                      => $node->numberOfFunctionsAndMethods(),
                'numTestedMethods'                => $node->numberOfTestedFunctionsAndMethods(),
                'linesExecutedPercent'            => $node->percentageOfExecutedLines()->asFloat(),
                'linesExecutedPercentAsString'    => $node->percentageOfExecutedLines()->asString(),
                'numExecutedLines'                => $node->numberOfExecutedLines(),
                'numExecutableLines'              => $node->numberOfExecutableLines(),
                'branchesExecutedPercent'         => $node->percentageOfExecutedBranches()->asFloat(),
                'branchesExecutedPercentAsString' => $node->percentageOfExecutedBranches()->asString(),
                'numExecutedBranches'             => $node->numberOfExecutedBranches(),
                'numExecutableBranches'           => $node->numberOfExecutableBranches(),
                'pathsExecutedPercent'            => $node->percentageOfExecutedPaths()->asFloat(),
                'pathsExecutedPercentAsString'    => $node->percentageOfExecutedPaths()->asString(),
                'numExecutedPaths'                => $node->numberOfExecutedPaths(),
                'numExecutablePaths'              => $node->numberOfExecutablePaths(),
                'testedMethodsPercent'            => $node->percentageOfTestedFunctionsAndMethods()->asFloat(),
                'testedMethodsPercentAsString'    => $node->percentageOfTestedFunctionsAndMethods()->asString(),
                'testedClassesPercent'            => $node->percentageOfTestedClassesAndTraits()->asFloat(),
                'testedClassesPercentAsString'    => $node->percentageOfTestedClassesAndTraits()->asString(),
                'crap'                            => '<abbr title="Change Risk Anti-Patterns (CRAP) Index">CRAP</abbr>',
            ],
        );

        $items .= $this->renderFunctionItems(
            $node->functions(),
            $methodItemTemplate,
        );

        $items .= $this->renderTraitOrClassItems(
            $node->traits(),
            $template,
            $methodItemTemplate,
        );

        $items .= $this->renderTraitOrClassItems(
            $node->classes(),
            $template,
            $methodItemTemplate,
        );

        return $items;
    }

    /**
     * @param array<string, ProcessedClassType|ProcessedTraitType> $items
     */
    private function renderTraitOrClassItems(array $items, Template $template, Template $methodItemTemplate): string
    {
        $buffer = '';

        if ($items === []) {
            return $buffer;
        }

        foreach ($items as $name => $item) {
            $numMethods       = 0;
            $numTestedMethods = 0;

            foreach ($item->methods as $method) {
                if ($method->executableLines > 0) {
                    $numMethods++;

                    if ($method->executedLines === $method->executableLines) {
                        $numTestedMethods++;
                    }
                }
            }

            if ($item->executableLines > 0) {
                $numClasses                   = 1;
                $numTestedClasses             = $numTestedMethods === $numMethods ? 1 : 0;
                $linesExecutedPercentAsString = Percentage::fromFractionAndTotal(
                    $item->executedLines,
                    $item->executableLines,
                )->asString();
                $branchesExecutedPercentAsString = Percentage::fromFractionAndTotal(
                    $item->executedBranches,
                    $item->executableBranches,
                )->asString();
                $pathsExecutedPercentAsString = Percentage::fromFractionAndTotal(
                    $item->executedPaths,
                    $item->executablePaths,
                )->asString();
            } else {
                $numClasses                      = 0;
                $numTestedClasses                = 0;
                $linesExecutedPercentAsString    = 'n/a';
                $branchesExecutedPercentAsString = 'n/a';
                $pathsExecutedPercentAsString    = 'n/a';
            }

            $testedMethodsPercentage = Percentage::fromFractionAndTotal(
                $numTestedMethods,
                $numMethods,
            );

            $testedClassesPercentage = Percentage::fromFractionAndTotal(
                $numTestedMethods === $numMethods ? 1 : 0,
                1,
            );

            $buffer .= $this->renderItemTemplate(
                $template,
                [
                    'name'                 => $this->abbreviateClassName($name),
                    'numClasses'           => $numClasses,
                    'numTestedClasses'     => $numTestedClasses,
                    'numMethods'           => $numMethods,
                    'numTestedMethods'     => $numTestedMethods,
                    'linesExecutedPercent' => Percentage::fromFractionAndTotal(
                        $item->executedLines,
                        $item->executableLines,
                    )->asFloat(),
                    'linesExecutedPercentAsString' => $linesExecutedPercentAsString,
                    'numExecutedLines'             => $item->executedLines,
                    'numExecutableLines'           => $item->executableLines,
                    'branchesExecutedPercent'      => Percentage::fromFractionAndTotal(
                        $item->executedBranches,
                        $item->executableBranches,
                    )->asFloat(),
                    'branchesExecutedPercentAsString' => $branchesExecutedPercentAsString,
                    'numExecutedBranches'             => $item->executedBranches,
                    'numExecutableBranches'           => $item->executableBranches,
                    'pathsExecutedPercent'            => Percentage::fromFractionAndTotal(
                        $item->executedPaths,
                        $item->executablePaths,
                    )->asFloat(),
                    'pathsExecutedPercentAsString' => $pathsExecutedPercentAsString,
                    'numExecutedPaths'             => $item->executedPaths,
                    'numExecutablePaths'           => $item->executablePaths,
                    'testedMethodsPercent'         => $testedMethodsPercentage->asFloat(),
                    'testedMethodsPercentAsString' => $testedMethodsPercentage->asString(),
                    'testedClassesPercent'         => $testedClassesPercentage->asFloat(),
                    'testedClassesPercentAsString' => $testedClassesPercentage->asString(),
                    'crap'                         => $item->crap,
                ],
            );

            foreach ($item->methods as $method) {
                $buffer .= $this->renderFunctionOrMethodItem(
                    $methodItemTemplate,
                    $method,
                    '&nbsp;',
                );
            }
        }

        return $buffer;
    }

    /**
     * @param array<string, ProcessedFunctionType> $functions
     */
    private function renderFunctionItems(array $functions, Template $template): string
    {
        if ($functions === []) {
            return '';
        }

        $buffer = '';

        foreach ($functions as $function) {
            $buffer .= $this->renderFunctionOrMethodItem(
                $template,
                $function,
            );
        }

        return $buffer;
    }

    private function renderFunctionOrMethodItem(Template $template, ProcessedFunctionType|ProcessedMethodType $item, string $indent = ''): string
    {
        $numMethods       = 0;
        $numTestedMethods = 0;

        if ($item->executableLines > 0) {
            $numMethods = 1;

            if ($item->executedLines === $item->executableLines) {
                $numTestedMethods = 1;
            }
        }

        $executedLinesPercentage = Percentage::fromFractionAndTotal(
            $item->executedLines,
            $item->executableLines,
        );

        $executedBranchesPercentage = Percentage::fromFractionAndTotal(
            $item->executedBranches,
            $item->executableBranches,
        );

        $executedPathsPercentage = Percentage::fromFractionAndTotal(
            $item->executedPaths,
            $item->executablePaths,
        );

        $testedMethodsPercentage = Percentage::fromFractionAndTotal(
            $numTestedMethods,
            1,
        );

        if ($item instanceof ProcessedFunctionType) {
            $name = $item->functionName;
        } else {
            $name = $item->methodName;
        }

        return $this->renderItemTemplate(
            $template,
            [
                'name' => sprintf(
                    '%s<a href="#%d"><abbr title="%s">%s</abbr></a>',
                    $indent,
                    $item->startLine,
                    htmlspecialchars($item->signature, self::HTML_SPECIAL_CHARS_FLAGS),
                    $this->escapeHtml($name),
                ),
                'numMethods'                      => $numMethods,
                'numTestedMethods'                => $numTestedMethods,
                'linesExecutedPercent'            => $executedLinesPercentage->asFloat(),
                'linesExecutedPercentAsString'    => $executedLinesPercentage->asString(),
                'numExecutedLines'                => $item->executedLines,
                'numExecutableLines'              => $item->executableLines,
                'branchesExecutedPercent'         => $executedBranchesPercentage->asFloat(),
                'branchesExecutedPercentAsString' => $executedBranchesPercentage->asString(),
                'numExecutedBranches'             => $item->executedBranches,
                'numExecutableBranches'           => $item->executableBranches,
                'pathsExecutedPercent'            => $executedPathsPercentage->asFloat(),
                'pathsExecutedPercentAsString'    => $executedPathsPercentage->asString(),
                'numExecutedPaths'                => $item->executedPaths,
                'numExecutablePaths'              => $item->executablePaths,
                'testedMethodsPercent'            => $testedMethodsPercentage->asFloat(),
                'testedMethodsPercentAsString'    => $testedMethodsPercentage->asString(),
                'crap'                            => $item->crap,
            ],
        );
    }

    /**
     * @return list<string>
     */
    private function highlightedSourceFor(FileNode $node): array
    {
        $path = $node->pathAsString();

        if ($path === '') {
            // @codeCoverageIgnoreStart
            return [];
            // @codeCoverageIgnoreEnd
        }

        return $this->syntaxHighlighter->highlight($path);
    }

    private function renderSourceWithLineCoverage(FileNode $node): string
    {
        $linesTemplate      = new Template($this->templatePath . 'lines.html.dist', '{{', '}}');
        $singleLineTemplate = new Template($this->templatePath . 'line.html.dist', '{{', '}}');

        $coverageData = $node->lineCoverageData();
        $testData     = $node->testData();
        $codeLines    = $this->highlightedSourceFor($node);
        $lines        = '';
        $i            = 1;

        foreach ($codeLines as $line) {
            $trClass        = '';
            $popoverContent = '';
            $popoverTitle   = '';

            if (array_key_exists($i, $coverageData)) {
                $numTests = ($coverageData[$i] !== null ? count($coverageData[$i]) : 0);

                if ($coverageData[$i] === null) {
                    $trClass = 'warning';
                } elseif ($numTests === 0) {
                    $trClass = 'danger';
                } else {
                    if ($numTests > 1) {
                        $popoverTitle = $numTests . ' tests cover line ' . $i;
                    } else {
                        $popoverTitle = '1 test covers line ' . $i;
                    }

                    $lineCss        = 'covered-by-large-tests';
                    $popoverContent = '<ul>';

                    foreach ($coverageData[$i] as $test) {
                        if (!isset($testData[$test])) {
                            // @codeCoverageIgnoreStart
                            continue;
                            // @codeCoverageIgnoreEnd
                        }

                        if ($lineCss === 'covered-by-large-tests' && $testData[$test]['size'] === 'medium') {
                            $lineCss = 'covered-by-medium-tests';
                        } elseif ($testData[$test]['size'] === 'small') {
                            $lineCss = 'covered-by-small-tests';
                        }

                        $popoverContent .= $this->createPopoverContentForTest($test, $testData[$test]);
                    }

                    $popoverContent .= '</ul>';
                    $trClass = $lineCss . ' popin';
                }
            }

            $popover = '';

            if ($popoverTitle !== '') {
                $popover = sprintf(
                    ' data-bs-title="%s" data-bs-content="%s" data-bs-placement="top" data-bs-html="true"',
                    $popoverTitle,
                    htmlspecialchars($popoverContent, self::HTML_SPECIAL_CHARS_FLAGS),
                );
            }

            $lines .= $this->renderLine($singleLineTemplate, $i, $line, $trClass, $popover);

            $i++;
        }

        $linesTemplate->setVar(['lines' => $lines]);

        return $linesTemplate->render();
    }

    private function renderViewTabs(string $fileName, string $activeView): string
    {
        $tabs = [
            'line'   => ['href' => $fileName . '.html', 'label' => 'Line Coverage'],
            'branch' => ['href' => $fileName . '_branch.html', 'label' => 'Branch Coverage'],
            'path'   => ['href' => $fileName . '_path.html', 'label' => 'Path Coverage'],
        ];

        $html = '   <ul class="nav nav-tabs mb-3">' . "\n";

        foreach ($tabs as $view => $tab) {
            $active = $view === $activeView ? ' active' : '';
            $aria   = $view === $activeView ? ' aria-current="page"' : '';

            $html .= sprintf(
                '    <li class="nav-item"><a class="nav-link%s" href="%s"%s>%s</a></li>' . "\n",
                $active,
                $tab['href'],
                $aria,
                $tab['label'],
            );
        }

        $html .= '   </ul>';

        return $html;
    }

    private function renderSourceWithBranchCoverage(FileNode $node): string
    {
        $linesTemplate      = new Template($this->templatePath . 'lines.html.dist', '{{', '}}');
        $singleLineTemplate = new Template($this->templatePath . 'line.html.dist', '{{', '}}');

        $functionCoverageData = $node->functionCoverageData();
        $testData             = $node->testData();
        $codeLines            = $this->highlightedSourceFor($node);

        $lineData          = [];
        $decisionPointData = [];

        foreach (array_keys($codeLines) as $line) {
            $lineData[$line + 1] = [
                'includedInBranches'    => 0,
                'includedInHitBranches' => 0,
                'tests'                 => [],
            ];
        }

        /** @var ProcessedFunctionCoverageData $method */
        foreach ($functionCoverageData as $method) {
            /** @var ProcessedBranchCoverageData $branch */
            foreach ($method->branches as $branchId => $branch) {
                if (count($branch->out) > 1) {
                    $decisionLine = max($branch->line_start, $branch->line_end);

                    if (isset($lineData[$decisionLine]) && !isset($decisionPointData[$decisionLine])) {
                        $targets = [];

                        foreach ($branch->out as $targetBranchId) {
                            if (isset($method->branches[$targetBranchId])) {
                                $targets[] = $method->branches[$targetBranchId]->hit !== [];
                            }
                        }

                        if (count($targets) > 1) {
                            $decisionPointData[$decisionLine] = $targets;
                        }
                    }
                }

                foreach (range($branch->line_start, $branch->line_end) as $line) {
                    if (!isset($lineData[$line])) { // blank line at end of file is sometimes included here
                        continue;
                    }

                    $lineData[$line]['includedInBranches']++;

                    if ($branch->hit !== []) {
                        $lineData[$line]['includedInHitBranches']++;
                        $lineData[$line]['tests'] = array_unique(array_merge($lineData[$line]['tests'], $branch->hit));
                    }
                }
            }
        }

        $lines = '';

        /** @var string $line */
        foreach ($codeLines as $index => $line) {
            $i       = $index + 1;
            $trClass = '';
            $popover = '';

            $coverageCount      = '';
            $coverageCountClass = 'coverage-count';

            $currentLineData = $lineData[$i] ?? [
                'includedInBranches'    => 0,
                'includedInHitBranches' => 0,
                'tests'                 => [],
            ];

            if ($currentLineData['includedInBranches'] > 0) {
                $lineCss = 'success';

                if ($currentLineData['includedInHitBranches'] === 0) {
                    $lineCss = 'danger';
                } elseif ($currentLineData['includedInHitBranches'] !== $currentLineData['includedInBranches']) {
                    $lineCss = 'warning';
                }

                if (isset($decisionPointData[$i])) {
                    $markers = '';

                    foreach ($decisionPointData[$i] as $isHit) {
                        $markers .= $isHit
                            ? '<span class="branch-hit">&bull;</span>'
                            : '<span class="branch-miss">&bull;</span>';
                    }

                    $coverageCount = $markers;
                }

                $popoverContent = '<ul>';

                if (count($currentLineData['tests']) === 1) {
                    $popoverTitle = '1 test covers line ' . $i;
                } else {
                    $popoverTitle = count($currentLineData['tests']) . ' tests cover line ' . $i;
                }
                $popoverTitle .= '. These are covering ' . $currentLineData['includedInHitBranches'] . ' out of the ' . $currentLineData['includedInBranches'] . ' code branches.';

                foreach ($currentLineData['tests'] as $test) {
                    if (!isset($testData[$test])) {
                        // @codeCoverageIgnoreStart
                        continue;
                        // @codeCoverageIgnoreEnd
                    }

                    $popoverContent .= $this->createPopoverContentForTest($test, $testData[$test]);
                }

                $popoverContent .= '</ul>';
                $trClass = $lineCss . ' popin';

                $popover = sprintf(
                    ' data-bs-title="%s" data-bs-content="%s" data-bs-placement="top" data-bs-html="true"',
                    $popoverTitle,
                    htmlspecialchars($popoverContent, self::HTML_SPECIAL_CHARS_FLAGS),
                );
            }

            $lines .= $this->renderLine($singleLineTemplate, $i, $line, $trClass, $popover, $coverageCount, $coverageCountClass);
        }

        $linesTemplate->setVar(['lines' => $lines]);

        return $linesTemplate->render();
    }

    private function renderSourceWithPathCoverage(FileNode $node): string
    {
        $linesTemplate      = new Template($this->templatePath . 'lines.html.dist', '{{', '}}');
        $singleLineTemplate = new Template($this->templatePath . 'line.html.dist', '{{', '}}');

        $functionCoverageData = $node->functionCoverageData();
        $testData             = $node->testData();
        $codeLines            = $this->highlightedSourceFor($node);

        $lineData = [];

        foreach (array_keys($codeLines) as $line) {
            $lineData[$line + 1] = [
                'includedInPaths'    => [],
                'includedInHitPaths' => [],
                'tests'              => [],
            ];
        }

        /** @var ProcessedFunctionCoverageData $method */
        foreach ($functionCoverageData as $method) {
            /** @var ProcessedPathCoverageData $path */
            foreach ($method->paths as $pathId => $path) {
                foreach ($path->path as $branchTaken) {
                    if (!isset($method->branches[$branchTaken])) {
                        // @codeCoverageIgnoreStart
                        continue;
                        // @codeCoverageIgnoreEnd
                    }

                    foreach (range($method->branches[$branchTaken]->line_start, $method->branches[$branchTaken]->line_end) as $line) {
                        if (!isset($lineData[$line])) {
                            continue;
                        }
                        $lineData[$line]['includedInPaths'][] = $pathId;

                        if ($path->hit !== []) {
                            $lineData[$line]['includedInHitPaths'][] = $pathId;
                            $lineData[$line]['tests']                = array_unique(array_merge($lineData[$line]['tests'], $path->hit));
                        }
                    }
                }
            }
        }

        $lines = '';

        /** @var string $line */
        foreach ($codeLines as $index => $line) {
            $i       = $index + 1;
            $trClass = '';
            $popover = '';

            $coverageCount      = '';
            $coverageCountClass = 'coverage-count';

            $currentLineData = $lineData[$i] ?? [
                'includedInPaths'    => [],
                'includedInHitPaths' => [],
                'tests'              => [],
            ];

            $includedInPathsCount    = count(array_unique($currentLineData['includedInPaths']));
            $includedInHitPathsCount = count(array_unique($currentLineData['includedInHitPaths']));

            if ($includedInPathsCount > 0) {
                $lineCss = 'success';

                if ($includedInHitPathsCount === 0) {
                    $lineCss = 'danger';
                } elseif ($includedInHitPathsCount !== $includedInPathsCount) {
                    $lineCss = 'warning';
                }

                $popoverContent = '<ul>';

                if (count($currentLineData['tests']) === 1) {
                    $popoverTitle = '1 test covers line ' . $i;
                } else {
                    $popoverTitle = count($currentLineData['tests']) . ' tests cover line ' . $i;
                }
                $popoverTitle .= '. These are covering ' . $includedInHitPathsCount . ' out of the ' . $includedInPathsCount . ' code paths.';

                foreach ($currentLineData['tests'] as $test) {
                    if (!isset($testData[$test])) {
                        // @codeCoverageIgnoreStart
                        continue;
                        // @codeCoverageIgnoreEnd
                    }

                    $popoverContent .= $this->createPopoverContentForTest($test, $testData[$test]);
                }

                $popoverContent .= '</ul>';
                $trClass = $lineCss . ' popin';

                $popover = sprintf(
                    ' data-bs-title="%s" data-bs-content="%s" data-bs-placement="top" data-bs-html="true"',
                    $popoverTitle,
                    htmlspecialchars($popoverContent, self::HTML_SPECIAL_CHARS_FLAGS),
                );
            }

            $lines .= $this->renderLine($singleLineTemplate, $i, $line, $trClass, $popover, $coverageCount, $coverageCountClass);
        }

        $linesTemplate->setVar(['lines' => $lines]);

        return $linesTemplate->render();
    }

    private function renderBranchStructure(FileNode $node): string
    {
        $branchesTemplate = new Template($this->templatePath . 'branches.html.dist', '{{', '}}');

        $coverageData = $this->sortedByStartLine($node->functionCoverageData());
        $testData     = $node->testData();
        $branches     = '';

        /** @var ProcessedFunctionCoverageData $methodData */
        foreach ($coverageData as $methodName => $methodData) {
            if ($methodData->branches === []) {
                continue;
            }

            $branchCount    = count($methodData->branches);
            $hitBranchCount = 0;

            foreach ($methodData->branches as $branch) {
                if ($branch->hit !== []) {
                    $hitBranchCount++;
                }
            }

            $badge = sprintf(
                ' <span class="%s">%d/%d</span>',
                $hitBranchCount === $branchCount ? 'success' : ($hitBranchCount === 0 ? 'danger' : 'warning'),
                $hitBranchCount,
                $branchCount,
            );

            $branches .= '<h5 class="structure-heading"><a name="' . htmlspecialchars($methodName, self::HTML_SPECIAL_CHARS_FLAGS) . '">' . $this->abbreviateMethodName($methodName) . '</a>' . $badge . '</h5>' . "\n";
            $branches .= '<table class="table table-bordered table-sm structure-table">' . "\n";
            $branches .= '<thead><tr><th>#</th><th>Lines</th><th>Status</th><th>Tests</th></tr></thead>' . "\n";
            $branches .= '<tbody>' . "\n";

            $branchIndex = 1;

            /** @var ProcessedBranchCoverageData $branch */
            foreach ($methodData->branches as $branch) {
                $lineStart  = min($branch->line_start, $branch->line_end);
                $lineEnd    = max($branch->line_start, $branch->line_end);
                $linesLabel = $lineStart === $lineEnd
                    ? sprintf('<a href="#%d">L%d</a>', $lineStart, $lineStart)
                    : sprintf('<a href="#%d">L%d</a>&ndash;<a href="#%d">L%d</a>', $lineStart, $lineStart, $lineEnd, $lineEnd);

                $numTests = count($branch->hit);

                if ($numTests === 0) {
                    $statusClass = 'danger';
                    $statusLabel = 'Not covered';
                    $testsLabel  = '&mdash;';
                } else {
                    $statusClass = 'success';
                    $statusLabel = 'Covered';

                    $popoverContent = '<ul>';

                    foreach ($branch->hit as $test) {
                        if (!isset($testData[$test])) {
                            // @codeCoverageIgnoreStart
                            continue;
                            // @codeCoverageIgnoreEnd
                        }

                        $popoverContent .= $this->createPopoverContentForTest($test, $testData[$test]);
                    }

                    $popoverContent .= '</ul>';

                    $testsLabel = sprintf(
                        '<span class="popin" data-bs-title="%s" data-bs-content="%s" data-bs-placement="top" data-bs-html="true">%s</span>',
                        $numTests === 1 ? '1 test' : $numTests . ' tests',
                        htmlspecialchars($popoverContent, self::HTML_SPECIAL_CHARS_FLAGS),
                        $numTests === 1 ? '1 test' : $numTests . ' tests',
                    );
                }

                $branches .= sprintf(
                    '<tr class="%s"><td>%d</td><td>%s</td><td>%s</td><td>%s</td></tr>' . "\n",
                    $statusClass,
                    $branchIndex,
                    $linesLabel,
                    $statusLabel,
                    $testsLabel,
                );

                $branchIndex++;
            }

            $branches .= '</tbody></table>' . "\n";
        }

        $branchesTemplate->setVar(['branches' => $branches]);

        return $branchesTemplate->render();
    }

    private function renderPathStructure(FileNode $node): string
    {
        $pathsTemplate = new Template($this->templatePath . 'paths.html.dist', '{{', '}}');

        $coverageData = $this->sortedByStartLine($node->functionCoverageData());
        $testData     = $node->testData();
        $paths        = '';

        /** @var ProcessedFunctionCoverageData $methodData */
        foreach ($coverageData as $methodName => $methodData) {
            if ($methodData->paths === []) {
                continue;
            }

            $pathCount    = count($methodData->paths);
            $hitPathCount = 0;

            foreach ($methodData->paths as $path) {
                if ($path->hit !== []) {
                    $hitPathCount++;
                }
            }

            $badge = sprintf(
                ' <span class="%s">%d/%d</span>',
                $hitPathCount === $pathCount ? 'success' : ($hitPathCount === 0 ? 'danger' : 'warning'),
                $hitPathCount,
                $pathCount,
            );

            $paths .= '<h5 class="structure-heading"><a name="' . htmlspecialchars($methodName, self::HTML_SPECIAL_CHARS_FLAGS) . '">' . $this->abbreviateMethodName($methodName) . '</a>' . $badge . '</h5>' . "\n";

            $renderedPaths = $methodData->paths;

            if ($pathCount > self::MAX_RENDERED_PATHS) {
                $renderedPaths = array_slice($methodData->paths, 0, self::MAX_RENDERED_PATHS, true);

                $paths .= '<details><summary>' . $pathCount . ' paths &mdash; click to expand</summary>' . "\n";
                $paths .= '<p>Only the first ' . self::MAX_RENDERED_PATHS . ' paths are shown, consider refactoring your code to bring the number of paths down.</p>' . "\n";
            }

            $paths .= '<table class="table table-bordered table-sm structure-table">' . "\n";
            $paths .= '<thead><tr><th>#</th><th>Branches</th><th>Status</th><th>Tests</th></tr></thead>' . "\n";
            $paths .= '<tbody>' . "\n";

            $pathIndex = 1;

            foreach ($renderedPaths as $path) {
                $branchLabels = [];

                foreach ($path->path as $branchId) {
                    if (!isset($methodData->branches[$branchId])) {
                        // @codeCoverageIgnoreStart
                        continue;
                        // @codeCoverageIgnoreEnd
                    }

                    $branch     = $methodData->branches[$branchId];
                    $branchLine = min($branch->line_start, $branch->line_end);

                    $branchLabels[] = sprintf('<a href="#%d">L%d</a>', $branchLine, $branchLine);
                }

                $branchesLabel = implode(' &rarr; ', $branchLabels);

                $numTests = count($path->hit);

                if ($numTests === 0) {
                    $statusClass = 'danger';
                    $statusLabel = 'Not covered';
                    $testsLabel  = '&mdash;';
                } else {
                    $statusClass = 'success';
                    $statusLabel = 'Covered';

                    $popoverContent = '<ul>';

                    foreach ($path->hit as $test) {
                        if (!isset($testData[$test])) {
                            // @codeCoverageIgnoreStart
                            continue;
                            // @codeCoverageIgnoreEnd
                        }

                        $popoverContent .= $this->createPopoverContentForTest($test, $testData[$test]);
                    }

                    $popoverContent .= '</ul>';

                    $testsLabel = sprintf(
                        '<span class="popin" data-bs-title="%s" data-bs-content="%s" data-bs-placement="top" data-bs-html="true">%s</span>',
                        $numTests === 1 ? '1 test' : $numTests . ' tests',
                        htmlspecialchars($popoverContent, self::HTML_SPECIAL_CHARS_FLAGS),
                        $numTests === 1 ? '1 test' : $numTests . ' tests',
                    );
                }

                $paths .= sprintf(
                    '<tr class="%s path-row" data-path-index="%d"><td>%d</td><td>%s</td><td>%s</td><td>%s</td></tr>' . "\n",
                    $statusClass,
                    $pathIndex - 1,
                    $pathIndex,
                    $branchesLabel,
                    $statusLabel,
                    $testsLabel,
                );

                $pathIndex++;
            }

            $paths .= '</tbody></table>' . "\n";

            $pathsJson = [];
            $pathIdx   = 0;

            foreach ($renderedPaths as $path) {
                $edges            = [];
                $previousBranchId = null;
                $lastBranchId     = null;

                foreach ($path->path as $branchId) {
                    if ($previousBranchId !== null) {
                        $edges[] = $previousBranchId . '-' . $branchId;
                    }

                    $previousBranchId = $branchId;
                    $lastBranchId     = $branchId;
                }

                if ($lastBranchId !== null && isset($methodData->branches[$lastBranchId])) {
                    foreach ($methodData->branches[$lastBranchId]->out as $dest) {
                        if ($dest === ControlFlowGraph::XDEBUG_EXIT_BRANCH) {
                            $edges[] = $lastBranchId . '-exit';
                        }
                    }
                }

                $pathsJson[$pathIdx] = $edges;
                $pathIdx++;
            }

            $svg = $this->controlFlowGraph()->renderSvg($methodData, $renderedPaths);

            $paths .= sprintf(
                '<div class="cfg-graph" data-paths="%s">%s</div>' . "\n",
                htmlspecialchars(json_encode($pathsJson, JSON_THROW_ON_ERROR), self::HTML_SPECIAL_CHARS_FLAGS),
                $svg,
            );

            if ($pathCount > self::MAX_RENDERED_PATHS) {
                $paths .= '</details>' . "\n";
            }
        }

        $pathsTemplate->setVar(['paths' => $paths]);

        return $pathsTemplate->render();
    }

    private function controlFlowGraph(): ControlFlowGraph
    {
        if ($this->controlFlowGraph === null) {
            $this->controlFlowGraph = new ControlFlowGraph;
        }

        return $this->controlFlowGraph;
    }

    /**
     * @param array<string, ProcessedFunctionCoverageData> $coverageData
     *
     * @return array<string, ProcessedFunctionCoverageData>
     */
    private function sortedByStartLine(array $coverageData): array
    {
        uasort(
            $coverageData,
            static function (ProcessedFunctionCoverageData $a, ProcessedFunctionCoverageData $b): int
            {
                return self::startLine($a) <=> self::startLine($b);
            },
        );

        return $coverageData;
    }

    private function renderLine(Template $template, int $lineNumber, string $lineContent, string $class, string $popover, string $coverageCount = '', string $coverageCountClass = 'col-0'): string
    {
        $template->setVar(
            [
                'lineNumber'         => (string) $lineNumber,
                'lineContent'        => $lineContent,
                'class'              => $class,
                'popover'            => $popover,
                'coverageCount'      => $coverageCount,
                'coverageCountClass' => $coverageCountClass,
            ],
        );

        return $template->render();
    }

    private function abbreviateClassName(string $className): string
    {
        $tmp = explode('\\', $className);

        if (count($tmp) > 1) {
            return sprintf(
                '<abbr title="%s">%s</abbr>',
                $this->escapeHtml($className),
                $this->escapeHtml(array_pop($tmp)),
            );
        }

        return $this->escapeHtml($className);
    }

    private function abbreviateMethodName(string $methodName): string
    {
        $parts = explode('->', $methodName);

        if (count($parts) === 2) {
            return $this->abbreviateClassName($parts[0]) . '->' . $this->escapeHtml($parts[1]);
        }

        return $this->escapeHtml($methodName);
    }

    /**
     * @param TestType $testData
     */
    private function createPopoverContentForTest(string $test, array $testData): string
    {
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

        return sprintf(
            '<li%s>%s</li>',
            $testCSS,
            htmlspecialchars($test, self::HTML_SPECIAL_CHARS_FLAGS),
        );
    }

    private static function startLine(ProcessedFunctionCoverageData $methodData): int
    {
        $startLine = PHP_INT_MAX;

        foreach ($methodData->branches as $branch) {
            $startLine = min($startLine, $branch->line_start, $branch->line_end);
        }

        return $startLine;
    }
}
