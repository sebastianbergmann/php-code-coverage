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
use function array_pop;
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
 */
final class Class_ extends Renderer
{
    public function render(ClassNode $node, string $file): void
    {
        $templateName = $this->templatePath . ($this->hasBranchCoverage ? 'class_branch.html' : 'class.html');
        $template     = new Template($templateName, '{{', '}}');

        $this->setCommonTemplateVariablesForClass($template, $node);

        $sections = $this->renderSourceSections($node);

        $template->setVar(
            [
                'items'    => $this->renderItems($node),
                'sections' => $sections,
                'legend'   => '<p><span class="legend covered-by-small-tests">Covered by small (and larger) tests</span><span class="legend covered-by-medium-tests">Covered by medium (and large) tests</span><span class="legend covered-by-large-tests">Covered by large tests (and tests of unknown size)</span><span class="legend not-covered">Not covered</span><span class="legend not-coverable">Not coverable</span></p>',
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
                'low_upper_bound'  => (string) $this->thresholds->lowUpperBound(),
                'high_lower_bound' => (string) $this->thresholds->highLowerBound(),
                'view_switcher'    => $this->viewSwitcher($pathToRoot, 'classes', $node->fileNode()->id() . '.html'),
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
                '         <li class="breadcrumb-item"><a href="%sindex.html">%s</a></li>' . "\n",
                array_pop($pathToRoot),
                $step->name(),
            );
        }

        $breadcrumbs .= sprintf(
            '         <li class="breadcrumb-item active">%s</li>' . "\n",
            $node->shortName(),
        );

        return $breadcrumbs;
    }

    private function renderItems(ClassNode $node): string
    {
        $templateName = $this->templatePath . ($this->hasBranchCoverage ? 'class_item_branch.html' : 'class_item.html');
        $template     = new Template($templateName, '{{', '}}');

        $methodTemplateName = $this->templatePath . ($this->hasBranchCoverage ? 'method_item_branch.html' : 'method_item.html');
        $methodItemTemplate = new Template($methodTemplateName, '{{', '}}');

        $items = $this->renderItemTemplate(
            $template,
            [
                'name'                            => 'Total',
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
                'crap'                            => '<abbr title="Change Risk Anti-Patterns (CRAP) Index">CRAP</abbr>',
            ],
        );

        // Own methods
        foreach ($node->class_()->methods as $method) {
            $items .= $this->renderMethodItem($methodItemTemplate, $method);
        }

        // Trait methods
        foreach ($node->traitSections() as $section) {
            foreach ($section->trait->methods as $methodName => $method) {
                $items .= $this->renderMethodItem(
                    $methodItemTemplate,
                    $method,
                    '&nbsp;<small>[' . htmlspecialchars($section->traitName, self::HTML_SPECIAL_CHARS_FLAGS) . ']</small> ',
                );
            }
        }

        // Inherited methods
        foreach ($node->parentSections() as $section) {
            foreach ($section->methods as $methodName => $method) {
                $items .= $this->renderMethodItem(
                    $methodItemTemplate,
                    $method,
                    '&nbsp;<small>[' . htmlspecialchars($section->className, self::HTML_SPECIAL_CHARS_FLAGS) . ']</small> ',
                );
            }
        }

        return $items;
    }

    private function renderMethodItem(Template $template, ProcessedMethodType $method, string $indent = '&nbsp;'): string
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
                    '%s<a href="#%d"><abbr title="%s">%s</abbr></a>',
                    $indent,
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
        );

        // Trait source sections
        foreach ($node->traitSections() as $section) {
            $sections .= $this->renderSectionHeader('From ' . $section->traitName);

            $sections .= $this->renderSourceSection(
                $section->traitName,
                $section->filePath,
                $section->startLine,
                $section->endLine,
                $section->fileNode->lineCoverageData(),
                $section->fileNode->testData(),
            );
        }

        // Parent source sections
        foreach ($node->parentSections() as $section) {
            $sections .= $this->renderSectionHeader('Inherited from ' . $section->className);

            foreach ($section->methods as $method) {
                $sections .= $this->renderSourceSection(
                    $section->className . '::' . $method->methodName,
                    $section->filePath,
                    $method->startLine,
                    $method->endLine,
                    $section->fileNode->lineCoverageData(),
                    $section->fileNode->testData(),
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
     * @param array<int, ?list<non-empty-string>> $coverageData
     * @param array<string, array>                $testData
     */
    private function renderSourceSection(string $label, string $filePath, int $startLine, int $endLine, array $coverageData, array $testData): string
    {
        $linesTemplate      = new Template($this->templatePath . 'lines.html.dist', '{{', '}}');
        $singleLineTemplate = new Template($this->templatePath . 'line.html.dist', '{{', '}}');

        $codeLines = $this->loadFile($filePath);
        $lines     = '';

        for ($i = $startLine; $i <= $endLine; $i++) {
            $lineIndex = $i - 1;

            if (!isset($codeLines[$lineIndex])) {
                continue;
            }

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
                $popover = sprintf(
                    ' data-bs-title="%s" data-bs-content="%s" data-bs-placement="top" data-bs-html="true"',
                    $popoverTitle,
                    htmlspecialchars($popoverContent, self::HTML_SPECIAL_CHARS_FLAGS),
                );
            }

            $lines .= $this->renderLine($singleLineTemplate, $i, $codeLines[$lineIndex], $trClass, $popover);
        }

        $linesTemplate->setVar(['lines' => $lines]);

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

        // One extra level for the _classes/ directory
        $depth++;

        return str_repeat('../', $depth);
    }
}
