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
use function htmlspecialchars;
use function sprintf;
use function str_repeat;
use function substr_count;
use function usort;
use SebastianBergmann\CodeCoverage\Data\ProcessedClassType;
use SebastianBergmann\CodeCoverage\Data\ProcessedMethodType;
use SebastianBergmann\CodeCoverage\FileCouldNotBeWrittenException;
use SebastianBergmann\CodeCoverage\Node\CrapIndex;
use SebastianBergmann\CodeCoverage\Report\Html\BubbleChart;
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
final class Dashboard extends Renderer
{
    public function render(NamespaceNode $node, string $file): void
    {
        $classes      = $node->allClassTypes();
        $templateName = $this->templateNameForTier('dashboard');
        $template     = new Template($templateName, '{{', '}}');

        $this->setCommonTemplateVariablesForNamespace($template, $node);

        $pathToRoot     = $this->pathToRootForNamespace($node);
        $bubbleChart    = new BubbleChart($this->thresholds);
        $classPageLinks = $this->views->fileView() ? [] : $this->classPageLinks($node);

        $template->setVar(
            [
                'class_bubble_chart'  => $bubbleChart->render($this->classItems($classes, $pathToRoot, $classPageLinks)),
                'class_crap_table'    => $this->classCrapTable($classes, $pathToRoot, $classPageLinks),
                'method_bubble_chart' => $bubbleChart->render($this->methodItems($classes, $pathToRoot, $classPageLinks)),
                'method_crap_table'   => $this->methodCrapTable($classes, $pathToRoot, $classPageLinks),
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
                'breadcrumbs'      => $this->breadcrumbsForDashboard($node),
                'date'             => $this->date,
                'version'          => $this->version,
                'runtime'          => $this->runtimeString(),
                'generator'        => $this->generator,
                'low_upper_bound'  => (string) $this->thresholds->lowUpperBound(),
                'high_lower_bound' => (string) $this->thresholds->highLowerBound(),
                'view_switcher'    => $this->views->fileView() ? $this->viewSwitcher($pathToRoot, 'classes') : '',
            ],
        );
    }

    protected function breadcrumbsForDashboard(NamespaceNode $node): string
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
                    '         <li class="breadcrumb-item"><a href="index.html">%s</a></li>' . "\n",
                    $step->name(),
                );
                $breadcrumbs .= '         <li class="breadcrumb-item active">(Dashboard)</li>' . "\n";
            }
        }

        return $breadcrumbs;
    }

    /**
     * @param array<string, ProcessedClassType> $classes
     * @param array<string, string>             $classPageLinks
     *
     * @return list<array{name: string, coverage: float|int, executableLines: int, complexity: int, link: string}>
     */
    private function classItems(array $classes, string $pathToRoot, array $classPageLinks): array
    {
        $items = [];

        foreach ($classes as $className => $class) {
            if ($class->executableLines === 0) {
                continue;
            }

            $items[] = [
                'name'            => $className,
                'coverage'        => $class->coverage,
                'executableLines' => $class->executableLines,
                'complexity'      => $class->ccn,
                'link'            => $pathToRoot . ($classPageLinks[$className] ?? $class->link),
            ];
        }

        return $items;
    }

    /**
     * @param array<string, ProcessedClassType> $classes
     * @param array<string, string>             $classPageLinks
     *
     * @return list<array{name: string, coverage: float|int, executableLines: int, complexity: int, link: string}>
     */
    private function methodItems(array $classes, string $pathToRoot, array $classPageLinks): array
    {
        $items = [];

        foreach ($classes as $className => $class) {
            foreach ($class->methods as $methodName => $method) {
                if ($method->executableLines === 0) {
                    continue;
                }

                $items[] = [
                    'name'            => $className . '::' . $methodName,
                    'coverage'        => $method->coverage,
                    'executableLines' => $method->executableLines,
                    'complexity'      => $method->ccn,
                    'link'            => $pathToRoot . $this->methodLink($classPageLinks, $className, $method),
                ];
            }
        }

        return $items;
    }

    /**
     * @param array<string, ProcessedClassType> $classes
     * @param array<string, string>             $classPageLinks
     */
    private function classCrapTable(array $classes, string $pathToRoot, array $classPageLinks): string
    {
        $items = [];

        foreach ($classes as $className => $class) {
            $items[] = [
                'name'     => $className,
                'coverage' => $class->coverage,
                'crap'     => (new CrapIndex($class->ccn, $class->coverage))->asString(),
                'link'     => $pathToRoot . ($classPageLinks[$className] ?? $class->link),
            ];
        }

        return $this->crapTable($items, 'Class');
    }

    /**
     * @param array<string, ProcessedClassType> $classes
     * @param array<string, string>             $classPageLinks
     */
    private function methodCrapTable(array $classes, string $pathToRoot, array $classPageLinks): string
    {
        $items = [];

        foreach ($classes as $className => $class) {
            foreach ($class->methods as $methodName => $method) {
                $items[] = [
                    'name'     => $className . '::' . $methodName,
                    'coverage' => $method->coverage,
                    'crap'     => (new CrapIndex($method->ccn, $method->coverage))->asString(),
                    'link'     => $pathToRoot . $this->methodLink($classPageLinks, $className, $method),
                ];
            }
        }

        return $this->crapTable($items, 'Method');
    }

    /**
     * @param array<string, string> $classPageLinks
     */
    private function methodLink(array $classPageLinks, string $className, ProcessedMethodType $method): string
    {
        if (isset($classPageLinks[$className])) {
            return $classPageLinks[$className] . '#' . $method->startLine;
        }

        return $method->link;
    }

    /**
     * Maps class names to class page paths relative to the class view root.
     *
     * The links carried by the processed class and method types point into
     * the file view; when that view is not rendered, the dashboard has to
     * link to the class pages instead.
     *
     * @return array<string, string>
     */
    private function classPageLinks(NamespaceNode $node): array
    {
        $links = [];

        foreach ($node->iterate() as $descendant) {
            if (!$descendant instanceof ClassNode) {
                continue;
            }

            $nsId = $descendant->parent()->id();

            $links[$descendant->className()] = ($nsId === 'index' ? '' : $nsId . '/') . $descendant->shortName() . '.html';
        }

        return $links;
    }

    /**
     * @param list<array{name: string, coverage: float|int, crap: string, link: string}> $items
     */
    private function crapTable(array $items, string $entityLabel): string
    {
        usort($items, static fn (array $a, array $b): int => ((float) $b['crap'] <=> (float) $a['crap']));

        $html = '<table class="table">' . "\n";
        $html .= ' <thead>' . "\n";
        $html .= '  <tr>' . "\n";
        $html .= sprintf('   <th>%s</th>' . "\n", $entityLabel);
        $html .= '   <th class="text-right"><abbr title="Change Risk Anti-Patterns (CRAP) Index">CRAP</abbr></th>' . "\n";
        $html .= '   <th class="text-right">Coverage</th>' . "\n";
        $html .= '  </tr>' . "\n";
        $html .= ' </thead>' . "\n";
        $html .= ' <tbody>' . "\n";

        foreach ($items as $item) {
            $html .= sprintf(
                '  <tr><td><a href="%s">%s</a></td><td class="text-right">%s</td><td class="text-right">%s%%</td></tr>' . "\n",
                htmlspecialchars($item['link']),
                htmlspecialchars($item['name']),
                $item['crap'],
                sprintf('%.1f', $item['coverage']),
            );
        }

        $html .= ' </tbody>' . "\n";
        $html .= '</table>';

        return $html;
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
