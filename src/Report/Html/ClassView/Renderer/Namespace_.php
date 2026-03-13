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
 */
final class Namespace_ extends Renderer
{
    public function render(NamespaceNode $node, string $file): void
    {
        $templateName = $this->templatePath . ($this->hasBranchCoverage ? 'namespace_branch.html' : 'namespace.html');
        $template     = new Template($templateName, '{{', '}}');

        $this->setCommonTemplateVariablesForNamespace($template, $node);

        $items = $this->renderItem($node);

        foreach ($node->childNamespaces() as $ns) {
            $items .= $this->renderItem($node, $ns);
        }

        foreach ($node->classes() as $class) {
            $items .= $this->renderClassItem($class);
        }

        $template->setVar(
            [
                'id'    => $node->id(),
                'items' => $items,
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
                'low_upper_bound'  => (string) $this->thresholds->lowUpperBound(),
                'high_lower_bound' => (string) $this->thresholds->highLowerBound(),
                'view_switcher'    => $this->viewSwitcher($pathToRoot, 'classes'),
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
                    '         <li class="breadcrumb-item"><a href="%sindex.html">%s</a></li>' . "\n",
                    array_pop($pathToRoot),
                    $step->name(),
                );
            } else {
                $breadcrumbs .= sprintf(
                    '         <li class="breadcrumb-item active">%s</li>' . "\n",
                    $step->name(),
                );
                $breadcrumbs .= '         <li class="breadcrumb-item">(<a href="dashboard.html">Dashboard</a>)</li>' . "\n";
            }
        }

        return $breadcrumbs;
    }

    private function renderItem(NamespaceNode $currentPage, ?NamespaceNode $child = null): string
    {
        $statsNode = $child ?? $currentPage;

        $data = [
            'numClasses'                      => $statsNode->numberOfClasses(),
            'numTestedClasses'                => $statsNode->numberOfTestedClasses(),
            'numMethods'                      => $statsNode->numberOfMethods(),
            'numTestedMethods'                => $statsNode->numberOfTestedMethods(),
            'linesExecutedPercent'            => $statsNode->percentageOfExecutedLines()->asFloat(),
            'linesExecutedPercentAsString'    => $statsNode->percentageOfExecutedLines()->asString(),
            'numExecutedLines'                => $statsNode->numberOfExecutedLines(),
            'numExecutableLines'              => $statsNode->numberOfExecutableLines(),
            'branchesExecutedPercent'         => $statsNode->percentageOfExecutedBranches()->asFloat(),
            'branchesExecutedPercentAsString' => $statsNode->percentageOfExecutedBranches()->asString(),
            'numExecutedBranches'             => $statsNode->numberOfExecutedBranches(),
            'numExecutableBranches'           => $statsNode->numberOfExecutableBranches(),
            'pathsExecutedPercent'            => $statsNode->percentageOfExecutedPaths()->asFloat(),
            'pathsExecutedPercentAsString'    => $statsNode->percentageOfExecutedPaths()->asString(),
            'numExecutedPaths'                => $statsNode->numberOfExecutedPaths(),
            'numExecutablePaths'              => $statsNode->numberOfExecutablePaths(),
            'testedMethodsPercent'            => $statsNode->percentageOfTestedMethods()->asFloat(),
            'testedMethodsPercentAsString'    => $statsNode->percentageOfTestedMethods()->asString(),
            'testedClassesPercent'            => $statsNode->percentageOfTestedClasses()->asFloat(),
            'testedClassesPercentAsString'    => $statsNode->percentageOfTestedClasses()->asString(),
        ];

        if ($child === null) {
            $data['name'] = 'Total';
        } else {
            $data['icon'] = sprintf(
                '<img src="%s_icons/file-directory.svg" class="octicon" />',
                $this->pathToRootForNamespace($currentPage),
            );
            $data['name'] = sprintf(
                '<a href="%s/index.html">%s</a>',
                $child->name(),
                $child->name(),
            );
        }

        $templateName = $this->templatePath . ($this->hasBranchCoverage ? 'namespace_item_branch.html' : 'namespace_item.html');

        return $this->renderItemTemplate(
            new Template($templateName, '{{', '}}'),
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
            'icon'                            => sprintf(
                '<img src="%s_icons/file-code.svg" class="octicon" />',
                $this->pathToRootForNamespace($class->parent()),
            ),
            'name' => sprintf(
                '<a href="%s.html">%s</a>',
                $class->shortName(),
                $class->shortName(),
            ),
        ];

        $templateName = $this->templatePath . ($this->hasBranchCoverage ? 'namespace_item_branch.html' : 'namespace_item.html');

        return $this->renderItemTemplate(
            new Template($templateName, '{{', '}}'),
            $data,
        );
    }

    private function pathToRootForNamespace(NamespaceNode $node): string
    {
        $id    = $node->id();
        $depth = substr_count($id, '/');

        if ($id !== 'index') {
            $depth++;
        }

        // One extra level for the _classes/ directory
        $depth++;

        return str_repeat('../', $depth);
    }
}
