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
use SebastianBergmann\CodeCoverage\FileCouldNotBeWrittenException;
use SebastianBergmann\CodeCoverage\Node\CrapIndex;
use SebastianBergmann\CodeCoverage\Report\Html\BubbleChart;
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

        $pathToRoot  = $this->pathToRootForNamespace($node);
        $bubbleChart = new BubbleChart($this->thresholds);

        $template->setVar(
            [
                'class_bubble_chart'  => $bubbleChart->render($this->classItems($classes, $pathToRoot)),
                'class_crap_table'    => $this->classCrapTable($classes, $pathToRoot),
                'method_bubble_chart' => $bubbleChart->render($this->methodItems($classes, $pathToRoot)),
                'method_crap_table'   => $this->methodCrapTable($classes, $pathToRoot),
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
                'view_switcher'    => $this->viewSwitcher($pathToRoot, 'classes'),
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
     *
     * @return list<array{name: string, coverage: float|int, executableLines: int, complexity: int, link: string}>
     */
    private function classItems(array $classes, string $pathToRoot): array
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
                'link'            => $pathToRoot . $class->link,
            ];
        }

        return $items;
    }

    /**
     * @param array<string, ProcessedClassType> $classes
     *
     * @return list<array{name: string, coverage: float|int, executableLines: int, complexity: int, link: string}>
     */
    private function methodItems(array $classes, string $pathToRoot): array
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
                    'link'            => $pathToRoot . $method->link,
                ];
            }
        }

        return $items;
    }

    /**
     * @param array<string, ProcessedClassType> $classes
     */
    private function classCrapTable(array $classes, string $pathToRoot): string
    {
        $items = [];

        foreach ($classes as $className => $class) {
            $items[] = [
                'name'     => $className,
                'coverage' => $class->coverage,
                'crap'     => (new CrapIndex($class->ccn, $class->coverage))->asString(),
                'link'     => $pathToRoot . $class->link,
            ];
        }

        return $this->crapTable($items, 'Class');
    }

    /**
     * @param array<string, ProcessedClassType> $classes
     */
    private function methodCrapTable(array $classes, string $pathToRoot): string
    {
        $items = [];

        foreach ($classes as $className => $class) {
            foreach ($class->methods as $methodName => $method) {
                $items[] = [
                    'name'     => $className . '::' . $methodName,
                    'coverage' => $method->coverage,
                    'crap'     => (new CrapIndex($method->ccn, $method->coverage))->asString(),
                    'link'     => $pathToRoot . $method->link,
                ];
            }
        }

        return $this->crapTable($items, 'Method');
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

        // One extra level for the _classes/ directory
        $depth++;

        return str_repeat('../', $depth);
    }
}
