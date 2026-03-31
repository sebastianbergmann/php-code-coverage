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

use function htmlspecialchars;
use function sprintf;
use function str_replace;
use function usort;
use SebastianBergmann\CodeCoverage\Data\ProcessedClassType;
use SebastianBergmann\CodeCoverage\Data\ProcessedTraitType;
use SebastianBergmann\CodeCoverage\FileCouldNotBeWrittenException;
use SebastianBergmann\CodeCoverage\Node\AbstractNode;
use SebastianBergmann\CodeCoverage\Node\CrapIndex;
use SebastianBergmann\CodeCoverage\Node\Directory as DirectoryNode;
use SebastianBergmann\Template\Exception;
use SebastianBergmann\Template\Template;

/**
 * @internal This class is not covered by the backward compatibility promise for phpunit/php-code-coverage
 *
 * @no-named-arguments Parameter names are not covered by the backward compatibility promise for phpunit/php-code-coverage
 */
final class Dashboard extends Renderer
{
    public function render(DirectoryNode $node, string $file): void
    {
        $classes      = $node->classesAndTraits();
        $templateName = $this->templatePath . ($this->hasBranchCoverage ? 'dashboard_branch.html' : 'dashboard.html');
        $template     = new Template(
            $templateName,
            '{{',
            '}}',
        );

        $this->setCommonTemplateVariables($template, $node);

        $baseLink    = $node->id() . '/';
        $bubbleChart = new BubbleChart($this->thresholds);

        $template->setVar(
            [
                'class_bubble_chart'  => $bubbleChart->render($this->classItems($classes, $baseLink)),
                'class_crap_table'    => $this->classCrapTable($classes, $baseLink),
                'method_bubble_chart' => $bubbleChart->render($this->methodItems($classes, $baseLink)),
                'method_crap_table'   => $this->methodCrapTable($classes, $baseLink),
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

    protected function activeBreadcrumb(AbstractNode $node): string
    {
        return sprintf(
            '         <li class="breadcrumb-item"><a href="index.html">%s</a></li>' . "\n" .
            '         <li class="breadcrumb-item active">(Dashboard)</li>' . "\n",
            $node->name(),
        );
    }

    /**
     * @param array<string, ProcessedClassType|ProcessedTraitType> $classes
     *
     * @return list<array{name: string, coverage: float|int, executableLines: int, complexity: int, link: string}>
     */
    private function classItems(array $classes, string $baseLink): array
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
                'link'            => str_replace($baseLink, '', $class->link),
            ];
        }

        return $items;
    }

    /**
     * @param array<string, ProcessedClassType|ProcessedTraitType> $classes
     *
     * @return list<array{name: string, coverage: float|int, executableLines: int, complexity: int, link: string}>
     */
    private function methodItems(array $classes, string $baseLink): array
    {
        $items = [];

        foreach ($classes as $className => $class) {
            foreach ($class->methods as $methodName => $method) {
                if ($method->executableLines === 0) {
                    continue;
                }

                $name = $methodName;

                if ($className !== '*') {
                    $name = $className . '::' . $methodName;
                }

                $items[] = [
                    'name'            => $name,
                    'coverage'        => $method->coverage,
                    'executableLines' => $method->executableLines,
                    'complexity'      => $method->ccn,
                    'link'            => str_replace($baseLink, '', $method->link),
                ];
            }
        }

        return $items;
    }

    /**
     * @param array<string, ProcessedClassType|ProcessedTraitType> $classes
     */
    private function classCrapTable(array $classes, string $baseLink): string
    {
        $items = [];

        foreach ($classes as $className => $class) {
            $items[] = [
                'name'     => $className,
                'coverage' => $class->coverage,
                'crap'     => (new CrapIndex($class->ccn, $class->coverage))->asString(),
                'link'     => str_replace($baseLink, '', $class->link),
            ];
        }

        return $this->crapTable($items, 'Class');
    }

    /**
     * @param array<string, ProcessedClassType|ProcessedTraitType> $classes
     */
    private function methodCrapTable(array $classes, string $baseLink): string
    {
        $items = [];

        foreach ($classes as $className => $class) {
            foreach ($class->methods as $methodName => $method) {
                $name = $methodName;

                if ($className !== '*') {
                    $name = $className . '::' . $methodName;
                }

                $items[] = [
                    'name'     => $name,
                    'coverage' => $method->coverage,
                    'crap'     => (new CrapIndex($method->ccn, $method->coverage))->asString(),
                    'link'     => str_replace($baseLink, '', $method->link),
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
}
