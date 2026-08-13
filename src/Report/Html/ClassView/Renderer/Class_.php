<?php declare(strict_types=1);
/*
 * This file is part of phpunit/php-code-coverage.
 *
 * (c) Sebastian Bergmann <sebastian@phpunit.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace SebastianBergmann\CodeCoverage\Report\Html\ClassView\Renderer;

use function array_key_exists;
use function array_keys;
use function array_pop;
use function array_sum;
use function count;
use function htmlspecialchars;
use function sprintf;
use function str_repeat;
use function substr_count;
use SebastianBergmann\CodeCoverage\Data\ProcessedMethodType;
use SebastianBergmann\CodeCoverage\FileCouldNotBeWrittenException;
use SebastianBergmann\CodeCoverage\Report\Html\ClassView\Node\ClassNode;
use SebastianBergmann\CodeCoverage\Report\Html\Renderer;
use SebastianBergmann\CodeCoverage\Util\Percentage;
use SebastianBergmann\Template\Exception;
use SebastianBergmann\Template\Template;

/**
 * @internal This class is not covered by the backward compatibility promise for phpunit/php-code-coverage
 *
 * @no-named-arguments Parameter names are not covered by the backward compatibility promise for phpunit/php-code-coverage
 *
 * @phpstan-import-type TestDataType from \SebastianBergmann\CodeCoverage\Node\Builder
 * @phpstan-import-type TestIndexType from \SebastianBergmann\CodeCoverage\Data\ProcessedCodeCoverageData
 * @phpstan-import-type CoverageItemData from \SebastianBergmann\CodeCoverage\Report\Html\Renderer
 */
final class Class_ extends Renderer
{
    public function render(ClassNode $node, string $file): void
    {
        $templateName = $this->templateNameForTier('class');
        $template     = new Template($templateName, '{{', '}}');

        $this->setCommonTemplateVariablesForClass($template, $node);

        $sections = $this->renderSourceSections($node);

        $template->setVar(
            [
                'summary'  => $this->renderSummary($this->nodeData($node), 'Methods', 'Classes'),
                'items'    => $this->renderItems($node),
                'sections' => $sections,
                'legend'   => $this->lineCoverageLegend(),
            ],
        );

        try {
            $template->renderTo($file);
        } catch (Exception $e) {
            throw new FileCouldNotBeWrittenException(
                $e->getMessage(),
                $e->getCode(),
                $e,
            );
        }
    }

    protected function setCommonTemplateVariablesForClass(Template $template, ClassNode $node): void
    {
        $nsNode     = $node->parent();
        $pathToRoot = $this->pathToRootForClass($node);

        $template->setVar(
            [
                'id'               => $nsNode->id() . '/' . $node->shortName(),
                'full_path'        => $node->className(),
                'path_to_root'     => $pathToRoot,
                'breadcrumbs'      => $this->breadcrumbsForClass($node),
                'date'             => $this->date,
                'version'          => $this->version,
                'runtime'          => $this->runtimeString(),
                'generator'        => $this->generator,
                'test_size_filter' => $this->testSizeFilter(),
                'view_switcher'    => $this->views->fileView() ? $this->viewSwitcher($pathToRoot, 'classes', $node->fileNode()->id() . '.html') : '',
            ],
        );
    }

    protected function breadcrumbsForClass(ClassNode $node): string
    {
        $breadcrumbs = '';
        $nsPath      = $node->parent()->pathAsArray();
        $pathToRoot  = [];
        $max         = count($nsPath);

        for ($i = 0; $i < $max; $i++) {
            $pathToRoot[] = str_repeat('../', $i);
        }

        foreach ($nsPath as $step) {
            $breadcrumbs .= sprintf(
                '     <li><a href="%sindex.html">%s</a></li>' . "\n",
                array_pop($pathToRoot),
                $step->name(),
            );
        }

        $breadcrumbs .= sprintf(
            '     <li class="current">%s</li>' . "\n",
            $node->shortName(),
        );

        return $breadcrumbs;
    }

    /**
     * @return CoverageItemData
     */
    private function nodeData(ClassNode $node): array
    {
        return [
            'name'                            => '',
            'numClasses'                      => $node->numberOfMethods() > 0 ? 1 : 0,
            'numTestedClasses'                => ($node->numberOfMethods() > 0 && $node->numberOfTestedMethods() === $node->numberOfMethods()) ? 1 : 0,
            'numMethods'                      => $node->numberOfMethods(),
            'numTestedMethods'                => $node->numberOfTestedMethods(),
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
            'testedMethodsPercent'            => $node->percentageOfTestedMethods()->asFloat(),
            'testedMethodsPercentAsString'    => $node->percentageOfTestedMethods()->asString(),
            'testedClassesPercent'            => $node->percentageOfTestedClasses()->asFloat(),
            'testedClassesPercentAsString'    => $node->percentageOfTestedClasses()->asString(),
            'coverageDataJson'                => $this->coverageDataJsonForClassNode($node),
        ];
    }

    private function renderItems(ClassNode $node): string
    {
        $methodItemTemplate = $this->template($this->templateNameForTier('class_item'));

        $items = '';

        // Own methods
        foreach ($node->class_()->methods as $method) {
            $items .= $this->renderMethodItem($methodItemTemplate, $method);
        }

        // Trait methods
        foreach ($node->traitSections() as $i => $section) {
            foreach ($section->trait->methods as $methodName => $method) {
                $items .= $this->renderMethodItem(
                    $methodItemTemplate,
                    $method,
                    '&nbsp;<small>[' . htmlspecialchars($section->traitName, self::HTML_SPECIAL_CHARS_FLAGS) . ']</small> ',
                    'trait-' . $i . '-',
                );
            }
        }

        // Inherited methods
        foreach ($node->parentSections() as $i => $section) {
            foreach ($section->methods as $methodName => $method) {
                $items .= $this->renderMethodItem(
                    $methodItemTemplate,
                    $method,
                    '&nbsp;<small>[' . htmlspecialchars($section->className, self::HTML_SPECIAL_CHARS_FLAGS) . ']</small> ',
                    'parent-' . $i . '-',
                );
            }
        }

        return $items;
    }

    private function renderMethodItem(Template $template, ProcessedMethodType $method, string $indent = '&nbsp;', string $anchorPrefix = ''): string
    {
        $numMethods       = 0;
        $numTestedMethods = 0;

        if ($method->executableLines > 0) {
            $numMethods = 1;

            if ($method->executedLines === $method->executableLines) {
                $numTestedMethods = 1;
            }
        }

        $executedLinesPercentage = Percentage::fromFractionAndTotal(
            $method->executedLines,
            $method->executableLines,
        );

        $executedBranchesPercentage = Percentage::fromFractionAndTotal(
            $method->executedBranches,
            $method->executableBranches,
        );

        $executedPathsPercentage = Percentage::fromFractionAndTotal(
            $method->executedPaths,
            $method->executablePaths,
        );

        $testedMethodsPercentage = Percentage::fromFractionAndTotal(
            $numTestedMethods,
            1,
        );

        return $this->renderItemTemplate(
            $template,
            [
                'name' => sprintf(
                    '%s<a href="#%s%d"><abbr title="%s">%s</abbr></a>',
                    $indent,
                    $anchorPrefix,
                    $method->startLine,
                    htmlspecialchars($method->signature, self::HTML_SPECIAL_CHARS_FLAGS),
                    $method->methodName,
                ),
                'numMethods'                      => $numMethods,
                'numTestedMethods'                => $numTestedMethods,
                'linesExecutedPercent'            => $executedLinesPercentage->asFloat(),
                'linesExecutedPercentAsString'    => $executedLinesPercentage->asString(),
                'numExecutedLines'                => $method->executedLines,
                'numExecutableLines'              => $method->executableLines,
                'branchesExecutedPercent'         => $executedBranchesPercentage->asFloat(),
                'branchesExecutedPercentAsString' => $executedBranchesPercentage->asString(),
                'numExecutedBranches'             => $method->executedBranches,
                'numExecutableBranches'           => $method->executableBranches,
                'pathsExecutedPercent'            => $executedPathsPercentage->asFloat(),
                'pathsExecutedPercentAsString'    => $executedPathsPercentage->asString(),
                'numExecutedPaths'                => $method->executedPaths,
                'numExecutablePaths'              => $method->executablePaths,
                'testedMethodsPercent'            => $testedMethodsPercentage->asFloat(),
                'testedMethodsPercentAsString'    => $testedMethodsPercentage->asString(),
                'crap'                            => $method->crap,
                'coverageDataJson'                => $this->coverageDataJsonForFunctionOrMethod($method),
            ],
        );
    }

    private function renderSourceSections(ClassNode $node): string
    {
        $sections = '';

        // Own source
        $sections .= $this->renderSourceSection(
            $node->shortName(),
            $node->filePath(),
            $node->startLine(),
            $node->endLine(),
            $node->fileNode()->lineCoverageData(),
            $node->fileNode()->testData(),
            $node->fileNode()->collectsHitCounts(),
        );

        // Trait source sections
        foreach ($node->traitSections() as $i => $section) {
            $sections .= $this->renderSectionHeader('From ' . $section->traitName);

            $sections .= $this->renderSourceSection(
                $section->traitName,
                $section->filePath,
                $section->startLine,
                $section->endLine,
                $section->fileNode->lineCoverageData(),
                $section->fileNode->testData(),
                $section->fileNode->collectsHitCounts(),
                'trait-' . $i . '-',
            );
        }

        // Parent source sections
        foreach ($node->parentSections() as $i => $section) {
            $sections .= $this->renderSectionHeader('Inherited from ' . $section->className);

            foreach ($section->methods as $method) {
                $sections .= $this->renderSourceSection(
                    $section->className . '::' . $method->methodName,
                    $section->filePath,
                    $method->startLine,
                    $method->endLine,
                    $section->fileNode->lineCoverageData(),
                    $section->fileNode->testData(),
                    $section->fileNode->collectsHitCounts(),
                    'parent-' . $i . '-',
                );
            }
        }

        return $sections;
    }

    private function renderSectionHeader(string $title): string
    {
        $template = new Template($this->templatePath . 'section_header.html.dist', '{{', '}}');
        $template->setVar(['title' => htmlspecialchars($title, self::HTML_SPECIAL_CHARS_FLAGS)]);

        return $template->render();
    }

    /**
     * @param non-empty-string                                $filePath
     * @param array<int, ?array<TestIndexType, positive-int>> $coverageData
     * @param array<TestIndexType, TestDataType>              $testData
     */
    private function renderSourceSection(string $label, string $filePath, int $startLine, int $endLine, array $coverageData, array $testData, bool $collectsHitCounts, string $anchorPrefix = ''): string
    {
        $linesTemplate      = new Template($this->templatePath . 'lines.html.dist', '{{', '}}');
        $singleLineTemplate = $this->template($this->templatePath . 'line.html.dist');

        $codeLines = $this->syntaxHighlighter->highlight($filePath);
        $lines     = '';

        for ($i = $startLine; $i <= $endLine; $i++) {
            $lineIndex = $i - 1;

            if (!isset($codeLines[$lineIndex])) {
                continue;
            }

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

                    if ($collectsHitCounts) {
                        $coverageCount = (string) array_sum($coverageData[$i]);
                    }

                    $lineCss        = 'covered-by-large-tests';
                    $popoverContent = '<ul>';

                    foreach (array_keys($coverageData[$i]) as $test) {
                        if ($lineCss === 'covered-by-large-tests' && isset($testData[$test]) && $testData[$test]['size'] === 'medium') {
                            $lineCss = 'covered-by-medium-tests';
                        } elseif (isset($testData[$test]) && $testData[$test]['size'] === 'small') {
                            $lineCss = 'covered-by-small-tests';
                        }

                        if (isset($testData[$test])) {
                            $popoverContent .= $this->createPopoverContentForTest($test, $testData[$test]);
                        }
                    }

                    $popoverContent .= '</ul>';
                    $trClass = $lineCss . ' popin';
                }
            }

            $popover = '';

            if ($popoverTitle !== '') {
                $popover = $this->popoverAttributes($popoverTitle, $popoverContent);
            }

            $lines .= $this->renderLine($singleLineTemplate, $i, $codeLines[$lineIndex], $trClass, $popover, $anchorPrefix, $coverageCount);
        }

        $linesTemplate->setVar(['lines' => $lines, 'gutter' => $collectsHitCounts ? ' with-hit-counts' : '']);

        return $linesTemplate->render();
    }

    private function pathToRootForClass(ClassNode $node): string
    {
        $nsNode = $node->parent();
        $id     = $nsNode->id();
        $depth  = substr_count($id, '/');

        if ($id !== 'index') {
            $depth++;
        }

        if ($this->views->fileView()) {
            // One extra level for the _classes/ directory
            $depth++;
        }

        return str_repeat('../', $depth);
    }
}
