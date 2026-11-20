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

use const PHP_INT_MAX;
use function array_key_exists;
use function array_keys;
use function array_merge;
use function array_pop;
use function array_slice;
use function array_unique;
use function array_values;
use function count;
use function explode;
use function htmlspecialchars;
use function implode;
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
use SebastianBergmann\CodeCoverage\Driver\Driver;
use SebastianBergmann\CodeCoverage\FileCouldNotBeWrittenException;
use SebastianBergmann\CodeCoverage\Node\File as FileNode;
use SebastianBergmann\CodeCoverage\Util\Json;
use SebastianBergmann\CodeCoverage\Util\Percentage;
use SebastianBergmann\Template\Exception;
use SebastianBergmann\Template\Template;

/**
 * @internal This class is not covered by the backward compatibility promise for phpunit/php-code-coverage
 *
 * @no-named-arguments Parameter names are not covered by the backward compatibility promise for phpunit/php-code-coverage
 *
 * @phpstan-import-type TestIndexType from \SebastianBergmann\CodeCoverage\Data\ProcessedCodeCoverageData
 * @phpstan-import-type TestDataType from \SebastianBergmann\CodeCoverage\Node\Builder
 * @phpstan-import-type CoverageItemData from \SebastianBergmann\CodeCoverage\Report\Html\Renderer
 *
 * @phpstan-type BranchEdgeType array{
 *     number: null|positive-int,
 *     lineStart: int,
 *     lineEnd: int,
 *     backward: bool,
 *     state: 'hit'|'miss'|'unknown',
 * }
 */
final class File extends Renderer
{
    /**
     * Maximum number of paths per method for which table rows, interactive
     * graph highlighting data, and graph edge classes are rendered; path
     * counts grow combinatorially and would bloat the report otherwise.
     */
    private const int MAX_RENDERED_PATHS = 100;

    /**
     * Maximum number of outcomes of decisions that are shown as individual
     * markers next to a line. The number of outcomes that were taken and the
     * number of outcomes are shown instead when there are more: a match
     * expression can have dozens of outcomes.
     */
    private const int MAX_RENDERED_OUTCOME_MARKERS = 8;
    private ?ControlFlowGraph $controlFlowGraph    = null;

    public function render(FileNode $node, string $file): void
    {
        $template = new Template($this->templateNameForTier('file'), '{{', '}}');
        $this->setCommonTemplateVariables($template, $node);

        $template->setVar(
            [
                'summary'   => $this->renderSummary($this->nodeData($node), 'Functions and Methods', 'Classes and Traits'),
                'items'     => $this->renderItems($node),
                'lines'     => $this->renderSourceWithLineCoverage($node),
                'legend'    => $this->lineCoverageLegend($node->collectsHitCounts()),
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
                    'legend'    => $this->branchCoverageLegend(true),
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
                    'legend'    => $this->branchCoverageLegend(false),
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

    /**
     * @return CoverageItemData
     */
    private function nodeData(FileNode $node): array
    {
        return [
            'name'                            => '',
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
            'coverageDataJson'                => $this->coverageDataJsonFor($node),
        ];
    }

    private function renderItems(FileNode $node): string
    {
        $template = $this->template($this->templateNameForTier('file_item'));

        $methodItemTemplate = new Template(
            $this->templateNameForTier('method_item'),
            '{{',
            '}}',
        );

        $items = $this->renderFunctionItems(
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
                    'coverageDataJson'             => $this->coverageDataJsonForClassOrTrait($item),
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
                'coverageDataJson'                => $this->coverageDataJsonForFunctionOrMethod($item),
            ],
        );
    }

    private function coverageDataJsonForClassOrTrait(ProcessedClassType|ProcessedTraitType $item): string
    {
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

        $numClasses       = 0;
        $numTestedClasses = 0;

        if ($item->executableLines > 0) {
            $numClasses = 1;

            if ($numTestedMethods === $numMethods) {
                $numTestedClasses = 1;
            }
        }

        $data = [
            'linesTotal'   => $item->executableLines,
            'linesAll'     => $item->executedLines,
            'methodsTotal' => $numMethods,
            'methodsAll'   => $numTestedMethods,
            'classesTotal' => $numClasses,
            'classesAll'   => $numTestedClasses,
        ];

        foreach (self::TEST_SIZE_JSON_KEY_SUFFIXES as $combination => $suffix) {
            $numTestedMethodsByTestSize = 0;

            foreach ($item->methods as $method) {
                if ($method->executableLines > 0 && $method->executedLinesByTestSize[$combination] === $method->executableLines) {
                    $numTestedMethodsByTestSize++;
                }
            }

            $numTestedClassesByTestSize = 0;

            if ($numClasses === 1 && $numTestedMethodsByTestSize === $numMethods) {
                $numTestedClassesByTestSize = 1;
            }

            $data['lines' . $suffix]   = $item->executedLinesByTestSize[$combination];
            $data['methods' . $suffix] = $numTestedMethodsByTestSize;
            $data['classes' . $suffix] = $numTestedClassesByTestSize;
        }

        return $this->buildCoverageDataJson($data);
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
        $singleLineTemplate = $this->template($this->templatePath . 'line.html.dist');

        $coverageData      = $node->lineCoverageData();
        $collectsHitCounts = $node->collectsHitCounts();
        $testData          = $node->testData();
        $codeLines         = $this->highlightedSourceFor($node);
        $lines             = '';
        $i                 = 1;

        foreach ($codeLines as $line) {
            $trClass        = '';
            $popoverContent = '';
            $popoverTitle   = '';
            $coverageCount  = '';

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

                    $coverageCount = (string) $numTests;

                    if ($collectsHitCounts) {
                        [$coverageCount, $executions] = $this->executionCountFor($coverageData[$i]);

                        $popoverTitle .= $executions;
                    }

                    $lineCss        = 'covered-by-large-tests';
                    $popoverContent = '&lt;ul&gt;';

                    foreach (array_keys($coverageData[$i]) as $test) {
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

                    $popoverContent .= '&lt;/ul&gt;';
                    $trClass = $lineCss . ' popin';
                }
            }

            $popover = '';

            if ($popoverTitle !== '') {
                $popover = $this->popoverAttributes($popoverTitle, $popoverContent);
            }

            $lines .= $this->renderLine($singleLineTemplate, $i, $line, $trClass, $popover, '', $coverageCount);

            $i++;
        }

        $linesTemplate->setVar(['lines' => $lines, 'gutter' => ' with-hit-counts']);

        return $linesTemplate->render();
    }

    private function renderViewTabs(string $fileName, string $activeView): string
    {
        $tabs = [
            'line'   => ['href' => $fileName . '.html', 'label' => 'Line Coverage'],
            'branch' => ['href' => $fileName . '_branch.html', 'label' => 'Branch Coverage'],
            'path'   => ['href' => $fileName . '_path.html', 'label' => 'Path Coverage'],
        ];

        $html = '   <ul class="tabs">' . "\n";

        foreach ($tabs as $view => $tab) {
            $html .= sprintf(
                '    <li><a href="%s"%s>%s</a></li>' . "\n",
                $tab['href'],
                $view === $activeView ? ' aria-current="page"' : '',
                $tab['label'],
            );
        }

        return $html . '   </ul>' . "\n";
    }

    private function renderSourceWithBranchCoverage(FileNode $node): string
    {
        $linesTemplate      = new Template($this->templatePath . 'lines.html.dist', '{{', '}}');
        $singleLineTemplate = $this->template($this->templatePath . 'line.html.dist');

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
            $branchNumbers = $this->branchNumbers($method);

            /** @var ProcessedBranchCoverageData $branch */
            foreach ($method->branches as $branchId => $branch) {
                // A branch that more than one edge leads out of ends with a decision,
                // the outcomes of that decision are shown for the line the branch ends on
                if (count($branch->out) > 1 && isset($lineData[$branch->line_end], $branchNumbers[$branchId])) {
                    $edges = $this->edgesOutOf($branchId, $branch, $method->branches, $branchNumbers);

                    if (count($edges) > 1) {
                        $decisionPointData[$branch->line_end][] = [
                            'number' => $branchNumbers[$branchId],
                            'edges'  => $edges,
                        ];
                    }
                }

                foreach (range($branch->line_start, $branch->line_end) as $line) {
                    if (!isset($lineData[$line])) { // blank line at end of file is sometimes included here
                        continue;
                    }

                    $lineData[$line]['includedInBranches']++;

                    if ($branch->hit !== []) {
                        $lineData[$line]['includedInHitBranches']++;
                        $lineData[$line]['tests'] = array_unique(array_merge($lineData[$line]['tests'], array_keys($branch->hit)));
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

            $coverageCount = '';

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

                $popoverContent = '';
                $numTests       = count($currentLineData['tests']);

                if (isset($decisionPointData[$i])) {
                    $coverageCount  = $this->renderOutcomeMarkers($decisionPointData[$i]);
                    $popoverContent = htmlspecialchars(
                        $this->renderOutcomes($decisionPointData[$i], $numTests > 0),
                        self::HTML_SPECIAL_CHARS_FLAGS,
                    );
                }

                $popoverContent .= '&lt;ul&gt;';

                if ($numTests === 1) {
                    $popoverTitle = '1 test covers line ' . $i;
                } else {
                    $popoverTitle = $numTests . ' tests cover line ' . $i;
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

                $popoverContent .= '&lt;/ul&gt;';
                $trClass = $lineCss . ' popin';

                $popover = $this->popoverAttributes($popoverTitle, $popoverContent);
            }

            $lines .= $this->renderLine($singleLineTemplate, $i, $line, $trClass, $popover, '', $coverageCount);
        }

        $linesTemplate->setVar(['lines' => $lines, 'gutter' => ' with-hit-counts']);

        return $linesTemplate->render();
    }

    private function renderSourceWithPathCoverage(FileNode $node): string
    {
        $linesTemplate      = new Template($this->templatePath . 'lines.html.dist', '{{', '}}');
        $singleLineTemplate = $this->template($this->templatePath . 'line.html.dist');

        $functionCoverageData = $node->functionCoverageData();
        $testData             = $node->testData();
        $codeLines            = $this->highlightedSourceFor($node);

        $numberOfLines = count($codeLines);

        /** @var array<int, int> $pathsPerLine */
        $pathsPerLine = [];

        /** @var array<int, int> $hitPathsPerLine */
        $hitPathsPerLine = [];

        /** @var array<int, array<TestIndexType, true>> $testsPerLine */
        $testsPerLine = [];

        /** @var ProcessedFunctionCoverageData $method */
        foreach ($functionCoverageData as $method) {
            /** @var array<int, list<int>> $linesOfBranch */
            $linesOfBranch = [];

            /** @var ProcessedBranchCoverageData $branch */
            foreach ($method->branches as $branchId => $branch) {
                $linesOfBranch[$branchId] = [];

                foreach (range($branch->line_start, $branch->line_end) as $line) {
                    if ($line >= 1 && $line <= $numberOfLines) {
                        $linesOfBranch[$branchId][] = $line;
                    }
                }
            }

            /** @var ProcessedPathCoverageData $path */
            foreach ($method->paths as $path) {
                /** @var array<int, true> $linesOfPath */
                $linesOfPath = [];

                foreach ($path->path as $branchTaken) {
                    if (!isset($linesOfBranch[$branchTaken])) {
                        // @codeCoverageIgnoreStart
                        continue;
                        // @codeCoverageIgnoreEnd
                    }

                    foreach ($linesOfBranch[$branchTaken] as $line) {
                        $linesOfPath[$line] = true;
                    }
                }

                $hit = $path->hit !== [];

                foreach (array_keys($linesOfPath) as $line) {
                    $pathsPerLine[$line] = ($pathsPerLine[$line] ?? 0) + 1;

                    if ($hit) {
                        $hitPathsPerLine[$line] = ($hitPathsPerLine[$line] ?? 0) + 1;

                        foreach (array_keys($path->hit) as $test) {
                            $testsPerLine[$line][$test] = true;
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

            $currentLineData = [
                'tests' => array_keys($testsPerLine[$i] ?? []),
            ];

            $includedInPathsCount    = $pathsPerLine[$i] ?? 0;
            $includedInHitPathsCount = $hitPathsPerLine[$i] ?? 0;

            if ($includedInPathsCount > 0) {
                $lineCss = 'success';

                if ($includedInHitPathsCount === 0) {
                    $lineCss = 'danger';
                } elseif ($includedInHitPathsCount !== $includedInPathsCount) {
                    $lineCss = 'warning';
                }

                $popoverContent = '&lt;ul&gt;';

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

                $popoverContent .= '&lt;/ul&gt;';
                $trClass = $lineCss . ' popin';

                $popover = $this->popoverAttributes($popoverTitle, $popoverContent);
            }

            $lines .= $this->renderLine($singleLineTemplate, $i, $line, $trClass, $popover);
        }

        $linesTemplate->setVar(['lines' => $lines, 'gutter' => '']);

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
                ' <span class="badge %s">%d/%d</span>',
                $hitBranchCount === $branchCount ? 'success' : ($hitBranchCount === 0 ? 'danger' : 'warning'),
                $hitBranchCount,
                $branchCount,
            );

            $branches .= '   <h3 class="structure-heading"><a name="' . htmlspecialchars($methodName, self::HTML_SPECIAL_CHARS_FLAGS) . '">' . $this->abbreviateMethodName($methodName) . '</a>' . $badge . '</h3>' . "\n";
            $branches .= '   <table class="structure-table">' . "\n";
            $branches .= '    <thead><tr><th>#</th><th>Lines</th><th>Status</th><th>Leads to</th><th>Tests</th></tr></thead>' . "\n";
            $branches .= '    <tbody>' . "\n";

            $branchIndex   = 1;
            $branchNumbers = $this->branchNumbers($methodData);

            /** @var ProcessedBranchCoverageData $branch */
            foreach ($methodData->branches as $branchId => $branch) {
                $lineStart  = $branch->line_start;
                $lineEnd    = $branch->line_end;
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

                    $popoverContent = '&lt;ul&gt;';

                    foreach (array_keys($branch->hit) as $test) {
                        if (!isset($testData[$test])) {
                            // @codeCoverageIgnoreStart
                            continue;
                            // @codeCoverageIgnoreEnd
                        }

                        $popoverContent .= $this->createPopoverContentForTest($test, $testData[$test]);
                    }

                    $popoverContent .= '&lt;/ul&gt;';

                    $label = $numTests === 1 ? '1 test' : $numTests . ' tests';

                    $testsLabel = sprintf(
                        '<button type="button" class="popin"%s>%s</button>',
                        $this->popoverAttributes($label, $popoverContent),
                        $label,
                    );
                }

                $branches .= sprintf(
                    '     <tr class="%s"><td>%d</td><td>%s</td><td>%s</td><td>%s</td><td>%s</td></tr>' . "\n",
                    $statusClass,
                    $branchIndex,
                    $linesLabel,
                    $statusLabel,
                    $this->renderLeadsTo($this->edgesOutOf($branchId, $branch, $methodData->branches, $branchNumbers)),
                    $testsLabel,
                );

                $branchIndex++;
            }

            $branches .= '    </tbody>' . "\n" . '   </table>' . "\n";
        }

        $branchesTemplate->setVar(['branches' => $branches]);

        return $branchesTemplate->render();
    }

    /**
     * The branches of a function are numbered in the order in which they are
     * listed in the "Branches" section.
     *
     * @return array<int, positive-int>
     */
    private function branchNumbers(ProcessedFunctionCoverageData $function): array
    {
        $numbers = [];
        $number  = 1;

        foreach (array_keys($function->branches) as $branchId) {
            $numbers[$branchId] = $number;

            $number++;
        }

        return $numbers;
    }

    /**
     * The edges that lead out of a branch, each with the branch (or the exit of
     * the function) it leads to and whether it was taken.
     *
     * Whether the edge that leads to the exit of the function was taken is not
     * reported by the driver. An edge that leads to a branch that is not part
     * of the coverage data, because its code is ignored for instance, is left
     * out. Edges that lead to the same branch are one edge.
     *
     * @param array<int, ProcessedBranchCoverageData> $branches
     * @param array<int, positive-int>                $branchNumbers
     *
     * @return list<BranchEdgeType>
     */
    private function edgesOutOf(int $branchId, ProcessedBranchCoverageData $branch, array $branches, array $branchNumbers): array
    {
        $edges = [];

        foreach ($branch->out as $edge => $targetId) {
            if ($targetId === Driver::BRANCH_EXIT) {
                $edges[$targetId] ??= [
                    'number'    => null,
                    'lineStart' => 0,
                    'lineEnd'   => 0,
                    'backward'  => false,
                    'state'     => 'unknown',
                ];

                continue;
            }

            if (!isset($branches[$targetId], $branchNumbers[$targetId])) {
                continue;
            }

            $state = 'unknown';

            if (isset($branch->out_hit[$edge])) {
                $state = $branch->out_hit[$edge] > 0 ? 'hit' : 'miss';
            }

            if (isset($edges[$targetId])) {
                if ($state === 'hit' || $edges[$targetId]['state'] === 'unknown') {
                    $edges[$targetId]['state'] = $state;
                }

                continue;
            }

            $edges[$targetId] = [
                'number'    => $branchNumbers[$targetId],
                'lineStart' => $branches[$targetId]->line_start,
                'lineEnd'   => $branches[$targetId]->line_end,
                'backward'  => $targetId <= $branchId,
                'state'     => $state,
            ];
        }

        return array_values($edges);
    }

    /**
     * @param BranchEdgeType $edge
     */
    private function edgeLabel(array $edge): string
    {
        if ($edge['number'] === null) {
            return 'exit of the function';
        }

        if ($edge['lineStart'] === $edge['lineEnd']) {
            return sprintf('#%d (L%d)', $edge['number'], $edge['lineStart']);
        }

        return sprintf('#%d (L%d&ndash;L%d)', $edge['number'], $edge['lineStart'], $edge['lineEnd']);
    }

    /**
     * The markers that are shown next to a line that one or more decisions end
     * on: one marker for each outcome of a decision of which it is known
     * whether it was taken.
     *
     * @param non-empty-list<array{number: positive-int, edges: non-empty-list<BranchEdgeType>}> $decisions
     */
    private function renderOutcomeMarkers(array $decisions): string
    {
        $groups = '';
        $total  = 0;
        $taken  = 0;

        foreach ($decisions as $decision) {
            $markers = '';

            foreach ($decision['edges'] as $edge) {
                if ($edge['state'] === 'unknown') {
                    continue;
                }

                $total++;

                if ($edge['state'] === 'hit') {
                    $taken++;

                    $markers .= '<span class="branch-hit" aria-hidden="true" title="' . $this->edgeLabel($edge) . ': taken">&bull;</span>';
                } else {
                    $markers .= '<span class="branch-miss" aria-hidden="true" title="' . $this->edgeLabel($edge) . ': not taken">&#9702;</span>';
                }
            }

            if ($markers !== '') {
                $groups .= '<span class="branch-markers">' . $markers . '</span>';
            }
        }

        if ($total === 0) {
            return '';
        }

        if ($total > self::MAX_RENDERED_OUTCOME_MARKERS) {
            $groups = sprintf(
                '<span class="branch-markers %s" aria-hidden="true">%d/%d</span>',
                $taken === $total ? 'branch-hit' : 'branch-miss',
                $taken,
                $total,
            );
        }

        return $groups . sprintf(
            '<span class="visually-hidden">%d of %d outcomes taken</span>',
            $taken,
            $total,
        );
    }

    /**
     * The outcomes of the decisions that end on a line, for the popover of that line.
     *
     * @param non-empty-list<array{number: positive-int, edges: non-empty-list<BranchEdgeType>}> $decisions
     */
    private function renderOutcomes(array $decisions, bool $isFollowedByTests): string
    {
        $html = '';

        foreach ($decisions as $decision) {
            $html .= sprintf('<p class="popover-heading">Outcomes of branch #%d</p>', $decision['number']);
            $html .= '<ul class="branch-outcomes">';

            foreach ($decision['edges'] as $edge) {
                $label = $this->edgeLabel($edge);

                if ($edge['number'] !== null) {
                    $label = sprintf('<a href="#%d">%s</a>', $edge['lineStart'], $label);
                }

                if ($edge['backward']) {
                    $label = 'back to ' . $label;
                }

                $html .= match ($edge['state']) {
                    'hit'     => '<li><span class="branch-hit" aria-hidden="true">&bull;</span> ' . $label . ': taken</li>',
                    'miss'    => '<li><span class="branch-miss" aria-hidden="true">&#9702;</span> ' . $label . ': not taken</li>',
                    'unknown' => '<li><span class="branch-unknown" aria-hidden="true">&ndash;</span> ' . $label . ': not reported</li>',
                };
            }

            $html .= '</ul>';
        }

        if ($isFollowedByTests) {
            $html .= '<p class="popover-heading">Tests</p>';
        }

        return $html;
    }

    /**
     * Where a branch leads to, for the "Branches" section. Whether an edge was
     * taken is only shown for a branch that ends with a decision.
     *
     * @param list<BranchEdgeType> $edges
     */
    private function renderLeadsTo(array $edges): string
    {
        if ($edges === []) {
            return '&mdash;';
        }

        $isDecision = count($edges) > 1;
        $targets    = [];

        foreach ($edges as $edge) {
            $target = 'exit';

            if ($edge['number'] !== null) {
                $target = sprintf('<a href="#%d">#%d</a>', $edge['lineStart'], $edge['number']);
            }

            if ($isDecision) {
                $target = match ($edge['state']) {
                    'hit'     => '<span class="branch-hit" aria-hidden="true">&bull;</span> ' . $target . '<span class="visually-hidden"> (taken)</span>',
                    'miss'    => '<span class="branch-miss" aria-hidden="true">&#9702;</span> ' . $target . '<span class="visually-hidden"> (not taken)</span>',
                    'unknown' => '<span class="branch-unknown" aria-hidden="true">&ndash;</span> ' . $target,
                };
            }

            $targets[] = $target;
        }

        return implode(', ', $targets);
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
                ' <span class="badge %s">%d/%d</span>',
                $hitPathCount === $pathCount ? 'success' : ($hitPathCount === 0 ? 'danger' : 'warning'),
                $hitPathCount,
                $pathCount,
            );

            $paths .= '   <h3 class="structure-heading"><a name="' . htmlspecialchars($methodName, self::HTML_SPECIAL_CHARS_FLAGS) . '">' . $this->abbreviateMethodName($methodName) . '</a>' . $badge . '</h3>' . "\n";

            $renderedPaths = $methodData->paths;

            if ($pathCount > self::MAX_RENDERED_PATHS) {
                $renderedPaths = array_slice($methodData->paths, 0, self::MAX_RENDERED_PATHS, true);

                $paths .= '   <details><summary>' . $pathCount . ' paths &mdash; click to expand</summary>' . "\n";
                $paths .= '   <p>Only the first ' . self::MAX_RENDERED_PATHS . ' paths are shown, consider refactoring your code to bring the number of paths down.</p>' . "\n";
            }

            $paths .= '   <table class="structure-table">' . "\n";
            $paths .= '    <thead><tr><th>#</th><th>Branches</th><th>Status</th><th>Tests</th></tr></thead>' . "\n";
            $paths .= '    <tbody>' . "\n";

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
                    $branchLine = $branch->line_start;

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

                    $popoverContent = '&lt;ul&gt;';

                    foreach (array_keys($path->hit) as $test) {
                        if (!isset($testData[$test])) {
                            // @codeCoverageIgnoreStart
                            continue;
                            // @codeCoverageIgnoreEnd
                        }

                        $popoverContent .= $this->createPopoverContentForTest($test, $testData[$test]);
                    }

                    $popoverContent .= '&lt;/ul&gt;';

                    $label = $numTests === 1 ? '1 test' : $numTests . ' tests';

                    $testsLabel = sprintf(
                        '<button type="button" class="popin"%s>%s</button>',
                        $this->popoverAttributes($label, $popoverContent),
                        $label,
                    );
                }

                $paths .= sprintf(
                    '     <tr class="%s path-row" data-path-index="%d"><td>%d</td><td>%s</td><td>%s</td><td>%s</td></tr>' . "\n",
                    $statusClass,
                    $pathIndex - 1,
                    $pathIndex,
                    $branchesLabel,
                    $statusLabel,
                    $testsLabel,
                );

                $pathIndex++;
            }

            $paths .= '    </tbody>' . "\n" . '   </table>' . "\n";

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
                        if ($dest === Driver::BRANCH_EXIT) {
                            $edges[] = $lastBranchId . '-exit';
                        }
                    }
                }

                $pathsJson[$pathIdx] = $edges;
                $pathIdx++;
            }

            $svg = $this->controlFlowGraph()->renderSvg($methodName, $methodData, $renderedPaths);

            $paths .= sprintf(
                '   <div class="cfg-graph" data-paths="%s">%s</div>' . "\n",
                htmlspecialchars(Json::encode($pathsJson), self::HTML_SPECIAL_CHARS_FLAGS),
                $svg,
            );

            if ($pathCount > self::MAX_RENDERED_PATHS) {
                $paths .= '   </details>' . "\n";
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

    private static function startLine(ProcessedFunctionCoverageData $methodData): int
    {
        $startLine = PHP_INT_MAX;

        foreach ($methodData->branches as $branch) {
            $startLine = min($startLine, $branch->line_start);
        }

        return $startLine;
    }
}
