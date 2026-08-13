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

use function array_pop;
use function count;
use function sprintf;
use function str_repeat;
use function substr_count;
use SebastianBergmann\CodeCoverage\FileCouldNotBeWrittenException;
use SebastianBergmann\CodeCoverage\Report\Html\ClassView\Node\ClassNode;
use SebastianBergmann\CodeCoverage\Report\Html\ClassView\Node\NamespaceNode;
use SebastianBergmann\CodeCoverage\Report\Html\Renderer;
use SebastianBergmann\Template\Exception;
use SebastianBergmann\Template\Template;

/**
 * @internal This class is not covered by the backward compatibility promise for phpunit/php-code-coverage
 *
 * @no-named-arguments Parameter names are not covered by the backward compatibility promise for phpunit/php-code-coverage
 *
 * @phpstan-import-type CoverageItemData from \SebastianBergmann\CodeCoverage\Report\Html\Renderer
 */
final class Namespace_ extends Renderer
{
    public function render(NamespaceNode $node, string $file): void
    {
        $templateName = $this->templateNameForTier('namespace');
        $template     = new Template($templateName, '{{', '}}');

        $this->setCommonTemplateVariablesForNamespace($template, $node);

        $items = '';

        foreach ($node->childNamespaces() as $ns) {
            $items .= $this->renderItem($ns);
        }

        foreach ($node->classes() as $class) {
            $items .= $this->renderClassItem($class);
        }

        $template->setVar(
            [
                'id'      => $node->id(),
                'items'   => $items,
                'summary' => $this->renderSummary($this->itemData($node), 'Methods', 'Classes'),
                'legend'  => $this->thresholdsLegend(),
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

    protected function setCommonTemplateVariablesForNamespace(Template $template, NamespaceNode $node): void
    {
        $pathToRoot = $this->pathToRootForNamespace($node);

        $template->setVar(
            [
                'id'               => $node->id(),
                'full_path'        => $node->namespace() !== '' ? $node->namespace() : '(Global)',
                'path_to_root'     => $pathToRoot,
                'breadcrumbs'      => $this->breadcrumbsForNamespace($node),
                'date'             => $this->date,
                'version'          => $this->version,
                'runtime'          => $this->runtimeString(),
                'generator'        => $this->generator,
                'test_size_filter' => $this->testSizeFilter(),
                'view_switcher'    => $this->views->fileView() ? $this->viewSwitcher($pathToRoot, 'classes') : '',
            ],
        );
    }

    protected function breadcrumbsForNamespace(NamespaceNode $node): string
    {
        $breadcrumbs = '';
        $path        = $node->pathAsArray();
        $pathToRoot  = [];
        $max         = count($path);

        for ($i = 0; $i < $max; $i++) {
            $pathToRoot[] = str_repeat('../', $i);
        }

        foreach ($path as $step) {
            if ($step !== $node) {
                $breadcrumbs .= sprintf(
                    '     <li><a href="%sindex.html">%s</a></li>' . "\n",
                    array_pop($pathToRoot),
                    $step->name(),
                );
            } else {
                $breadcrumbs .= sprintf(
                    '     <li class="current">%s</li>' . "\n",
                    $step->name(),
                );
                $breadcrumbs .= '     <li class="secondary"><a href="dashboard.html">Dashboard</a></li>' . "\n";
            }
        }

        return $breadcrumbs;
    }

    /**
     * @return CoverageItemData
     */
    private function itemData(NamespaceNode $node): array
    {
        return [
            'name'                            => '',
            'numClasses'                      => $node->numberOfClasses(),
            'numTestedClasses'                => $node->numberOfTestedClasses(),
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
            'coverageDataJson'                => $this->coverageDataJsonForNamespace($node),
        ];
    }

    private function renderItem(NamespaceNode $node): string
    {
        $data = $this->itemData($node);

        $data['icon'] = '<span class="icon icon-directory" aria-hidden="true"></span>';
        $data['name'] = sprintf(
            '<a href="%s/index.html">%s</a>',
            $node->name(),
            $node->name(),
        );

        return $this->renderItemTemplate(
            $this->template($this->templateNameForTier('namespace_item')),
            $data,
        );
    }

    private function renderClassItem(ClassNode $class): string
    {
        $data = [
            'numClasses'                      => $class->numberOfMethods() > 0 ? 1 : 0,
            'numTestedClasses'                => ($class->numberOfMethods() > 0 && $class->numberOfTestedMethods() === $class->numberOfMethods()) ? 1 : 0,
            'numMethods'                      => $class->numberOfMethods(),
            'numTestedMethods'                => $class->numberOfTestedMethods(),
            'linesExecutedPercent'            => $class->percentageOfExecutedLines()->asFloat(),
            'linesExecutedPercentAsString'    => $class->percentageOfExecutedLines()->asString(),
            'numExecutedLines'                => $class->numberOfExecutedLines(),
            'numExecutableLines'              => $class->numberOfExecutableLines(),
            'branchesExecutedPercent'         => $class->percentageOfExecutedBranches()->asFloat(),
            'branchesExecutedPercentAsString' => $class->percentageOfExecutedBranches()->asString(),
            'numExecutedBranches'             => $class->numberOfExecutedBranches(),
            'numExecutableBranches'           => $class->numberOfExecutableBranches(),
            'pathsExecutedPercent'            => $class->percentageOfExecutedPaths()->asFloat(),
            'pathsExecutedPercentAsString'    => $class->percentageOfExecutedPaths()->asString(),
            'numExecutedPaths'                => $class->numberOfExecutedPaths(),
            'numExecutablePaths'              => $class->numberOfExecutablePaths(),
            'testedMethodsPercent'            => $class->percentageOfTestedMethods()->asFloat(),
            'testedMethodsPercentAsString'    => $class->percentageOfTestedMethods()->asString(),
            'testedClassesPercent'            => $class->percentageOfTestedClasses()->asFloat(),
            'testedClassesPercentAsString'    => $class->percentageOfTestedClasses()->asString(),
            'coverageDataJson'                => $this->coverageDataJsonForClassNode($class),
            'icon'                            => '<span class="icon icon-file" aria-hidden="true"></span>',
            'name'                            => sprintf(
                '<a href="%s.html">%s</a>',
                $class->shortName(),
                $class->shortName(),
            ),
        ];

        $templateName = $this->templateNameForTier('namespace_item');

        return $this->renderItemTemplate(
            new Template($templateName, '{{', '}}'),
            $data,
        );
    }

    private function coverageDataJsonForNamespace(NamespaceNode $node): string
    {
        $data = [
            'linesTotal'   => $node->numberOfExecutableLines(),
            'linesAll'     => $node->numberOfExecutedLines(),
            'methodsTotal' => $node->numberOfMethods(),
            'methodsAll'   => $node->numberOfTestedMethods(),
            'classesTotal' => $node->numberOfClasses(),
            'classesAll'   => $node->numberOfTestedClasses(),
        ];

        foreach (self::TEST_SIZE_JSON_KEY_SUFFIXES as $combination => $suffix) {
            $data['lines' . $suffix]   = $node->numberOfExecutedLinesByTestSize($combination);
            $data['methods' . $suffix] = $node->numberOfTestedMethodsByTestSize($combination);
            $data['classes' . $suffix] = $node->numberOfTestedClassesByTestSize($combination);
        }

        return $this->buildCoverageDataJson($data);
    }

    private function pathToRootForNamespace(NamespaceNode $node): string
    {
        $id    = $node->id();
        $depth = substr_count($id, '/');

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
