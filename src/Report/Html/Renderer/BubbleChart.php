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

use const ENT_XML1;
use function array_filter;
use function array_map;
use function ceil;
use function floor;
use function htmlspecialchars;
use function log10;
use function max;
use function sprintf;
use function sqrt;
use function usort;
use SebastianBergmann\CodeCoverage\Report\Thresholds;

/**
 * @internal This class is not covered by the backward compatibility promise for phpunit/php-code-coverage
 *
 * @no-named-arguments Parameter names are not covered by the backward compatibility promise for phpunit/php-code-coverage
 */
final readonly class BubbleChart
{
    private Thresholds $thresholds;

    public function __construct(Thresholds $thresholds)
    {
        $this->thresholds = $thresholds;
    }

    /**
     * @param list<array{name: string, coverage: float|int, executableLines: int, complexity: int, link: string}> $items
     */
    public function render(array $items): string
    {
        $items = array_filter(
            $items,
            static fn (array $item): bool => $item['executableLines'] > 0,
        );

        if ($items === []) {
            return '';
        }

        $maxExecutableLines = max(array_map(static fn (array $f): int => $f['executableLines'], $items));
        $maxComplexity      = max(1, max(array_map(static fn (array $f): int => $f['complexity'], $items)));

        usort($items, static fn (array $a, array $b): int => $b['executableLines'] <=> $a['executableLines']);

        $svgWidth      = 800;
        $svgHeight     = 400;
        $paddingLeft   = 60;
        $paddingRight  = 20;
        $paddingTop    = 20;
        $paddingBottom = 50;
        $plotWidth     = $svgWidth - $paddingLeft - $paddingRight;
        $plotHeight    = $svgHeight - $paddingTop - $paddingBottom;
        $maxRadius     = 20;
        $minRadius     = 4;

        // Calculate Y-axis grid step
        $yAxisMax   = $maxComplexity * 1.1;
        $rawStep    = $yAxisMax / 5;
        $magnitude  = 10 ** floor($rawStep > 0 ? log10($rawStep) : 0);
        $normalized = $rawStep / $magnitude;

        if ($normalized <= 1) {
            $gridStep = $magnitude;
        } elseif ($normalized <= 2) {
            $gridStep = 2 * $magnitude;
        } elseif ($normalized <= 5) {
            $gridStep = 5 * $magnitude;
        } else {
            $gridStep = 10 * $magnitude;
        }

        $gridStep = max(1, (int) $gridStep);
        $yAxisMax = $gridStep * (int) ceil($yAxisMax / $gridStep);

        if ($yAxisMax === 0) {
            $yAxisMax = 10;
        }

        $svg = sprintf(
            '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 %d %d" preserveAspectRatio="xMidYMid meet">' . "\n",
            $svgWidth,
            $svgHeight,
        );

        $svg .= <<<'CSS'
 <style>
  .bubble-success { fill: var(--phpunit-success-bar); opacity: 0.7; }
  .bubble-warning { fill: var(--phpunit-warning-bar); opacity: 0.7; }
  .bubble-danger { fill: var(--phpunit-danger-bar); opacity: 0.7; }
  .bubble-success:hover, .bubble-warning:hover, .bubble-danger:hover { opacity: 1; stroke: var(--bs-emphasis-color); stroke-width: 1.5; }
  .chart-grid { stroke: var(--bs-border-color); stroke-width: 0.5; stroke-dasharray: 4,4; opacity: 0.5; }
  .chart-axis { stroke: var(--bs-border-color); stroke-width: 1; }
  .chart-label { fill: var(--bs-body-color); font-size: 11px; font-family: var(--bs-font-sans-serif, sans-serif); }
  .chart-title { fill: var(--bs-body-color); font-size: 12px; font-family: var(--bs-font-sans-serif, sans-serif); }
 </style>

CSS;

        // Vertical grid lines (x-axis: 0%, 20%, 40%, 60%, 80%, 100%)
        for ($pct = 0; $pct <= 100; $pct += 20) {
            $x = $paddingLeft + ($pct / 100) * $plotWidth;

            $svg .= sprintf(
                ' <line x1="%.1f" y1="%d" x2="%.1f" y2="%d" class="chart-grid"/>' . "\n",
                $x,
                $paddingTop,
                $x,
                $paddingTop + $plotHeight,
            );

            $svg .= sprintf(
                ' <text x="%.1f" y="%d" text-anchor="middle" class="chart-label">%d%%</text>' . "\n",
                $x,
                $paddingTop + $plotHeight + 18,
                $pct,
            );
        }

        // Horizontal grid lines (y-axis)
        for ($val = 0; $val <= $yAxisMax; $val += $gridStep) {
            $y = $paddingTop + $plotHeight - ($val / $yAxisMax) * $plotHeight;

            $svg .= sprintf(
                ' <line x1="%d" y1="%.1f" x2="%d" y2="%.1f" class="chart-grid"/>' . "\n",
                $paddingLeft,
                $y,
                $paddingLeft + $plotWidth,
                $y,
            );

            $svg .= sprintf(
                ' <text x="%d" y="%.1f" text-anchor="end" dominant-baseline="middle" class="chart-label">%d</text>' . "\n",
                $paddingLeft - 8,
                $y,
                $val,
            );
        }

        // Axes
        $svg .= sprintf(
            ' <line x1="%d" y1="%d" x2="%d" y2="%d" class="chart-axis"/>' . "\n",
            $paddingLeft,
            $paddingTop,
            $paddingLeft,
            $paddingTop + $plotHeight,
        );

        $svg .= sprintf(
            ' <line x1="%d" y1="%d" x2="%d" y2="%d" class="chart-axis"/>' . "\n",
            $paddingLeft,
            $paddingTop + $plotHeight,
            $paddingLeft + $plotWidth,
            $paddingTop + $plotHeight,
        );

        // Axis titles
        $svg .= sprintf(
            ' <text x="%.1f" y="%d" text-anchor="middle" class="chart-title">Line Coverage (%%)</text>' . "\n",
            $paddingLeft + $plotWidth / 2,
            $svgHeight - 5,
        );

        $svg .= sprintf(
            ' <text x="15" y="%.1f" text-anchor="middle" transform="rotate(-90, 15, %.1f)" class="chart-title">Cyclomatic Complexity</text>' . "\n",
            $paddingTop + $plotHeight / 2,
            $paddingTop + $plotHeight / 2,
        );

        // Bubbles
        foreach ($items as $item) {
            $cx = $paddingLeft + ($item['coverage'] / 100) * $plotWidth;
            $cy = $paddingTop + $plotHeight - ($item['complexity'] / $yAxisMax) * $plotHeight;
            $r  = max($minRadius, sqrt($item['executableLines'] / $maxExecutableLines) * $maxRadius);

            $colorClass = 'bubble-' . $this->colorLevel($item['coverage']);
            $title      = htmlspecialchars(
                sprintf(
                    '%s — Coverage: %.1f%% | Lines: %d | Complexity: %d',
                    $item['name'],
                    $item['coverage'],
                    $item['executableLines'],
                    $item['complexity'],
                ),
                ENT_XML1,
            );

            $svg .= sprintf(
                ' <a href="%s"><circle cx="%.1f" cy="%.1f" r="%.1f" class="%s"><title>%s</title></circle></a>' . "\n",
                htmlspecialchars($item['link'], ENT_XML1),
                $cx,
                $cy,
                $r,
                $colorClass,
                $title,
            );
        }

        $svg .= '</svg>';

        return $svg;
    }

    private function colorLevel(float $percent): string
    {
        if ($percent <= $this->thresholds->lowUpperBound()) {
            return 'danger';
        }

        if ($percent < $this->thresholds->highLowerBound()) {
            return 'warning';
        }

        return 'success';
    }
}
