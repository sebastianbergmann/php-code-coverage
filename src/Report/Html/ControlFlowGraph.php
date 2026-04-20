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

use function count;
use function fclose;
use function fwrite;
use function implode;
use function max;
use function min;
use function preg_replace;
use function proc_close;
use function proc_open;
use function sprintf;
use function stream_get_contents;
use function stream_set_blocking;
use function strlen;
use function substr;
use SebastianBergmann\CodeCoverage\Data\ProcessedFunctionCoverageData;
use SebastianBergmann\CodeCoverage\Data\ProcessedPathCoverageData;

/**
 * @internal This class is not covered by the backward compatibility promise for phpunit/php-code-coverage
 *
 * @no-named-arguments Parameter names are not covered by the backward compatibility promise for phpunit/php-code-coverage
 */
final class ControlFlowGraph
{
    public const int XDEBUG_EXIT_BRANCH = 2147483645;
    private ?bool $dotAvailable         = null;
    private readonly Colors $colors;

    public function __construct(Colors $colors)
    {
        $this->colors = $colors;
    }

    /**
     * @param null|array<int, ProcessedPathCoverageData> $paths
     */
    public function renderSvg(ProcessedFunctionCoverageData $methodData, ?array $paths = null): string
    {
        $dot = $this->generateDot($methodData, $paths);

        return $this->dotToSvg($dot);
    }

    /**
     * @param null|array<int, ProcessedPathCoverageData> $paths
     */
    private function generateDot(ProcessedFunctionCoverageData $methodData, ?array $paths = null): string
    {
        $coveredFill   = $this->colors->successLow();
        $coveredBorder = $this->colors->successBar();
        $uncoveredFill = $this->colors->danger();
        $uncoveredEdge = $this->colors->dangerBar();

        $dot = "digraph {\n";
        $dot .= "  rankdir=TB;\n";
        $dot .= '  node [shape=box, style=filled, fontname="sans-serif", fontsize=11];' . "\n";
        $dot .= '  entry [label="entry", shape=oval, style=filled, fillcolor="#f5f5f5", color="#999999"];' . "\n";

        $hasExit       = false;
        $firstBranchId = null;

        foreach ($methodData->branches as $branchId => $branch) {
            if ($firstBranchId === null) {
                $firstBranchId = $branchId;
            }

            foreach ($branch->out as $destBranchId) {
                if ($destBranchId === self::XDEBUG_EXIT_BRANCH) {
                    $hasExit = true;
                }
            }

            $lineStart = min($branch->line_start, $branch->line_end);
            $lineEnd   = max($branch->line_start, $branch->line_end);
            $label     = $lineStart === $lineEnd
                ? sprintf('L%d', $lineStart)
                : sprintf('L%d-L%d', $lineStart, $lineEnd);

            if ($branch->hit !== []) {
                $fillColor = $coveredFill;
                $color     = $coveredBorder;
            } else {
                $fillColor = $uncoveredFill;
                $color     = $uncoveredEdge;
            }

            $dot .= sprintf(
                '  b%d [label="%s", fillcolor="%s", color="%s"];' . "\n",
                $branchId,
                $label,
                $fillColor,
                $color,
            );
        }

        if ($hasExit) {
            $dot .= '  exit [label="exit", shape=oval, style=filled, fillcolor="#f5f5f5", color="#999999"];' . "\n";
        }

        if ($firstBranchId !== null) {
            $dot .= sprintf("  entry -> b%d;\n", $firstBranchId);
        }

        $edgePathClasses = $this->buildEdgePathClasses($methodData, $paths);

        foreach ($methodData->branches as $branchId => $branch) {
            foreach ($branch->out as $edgeIndex => $destBranchId) {
                $destNode = $destBranchId === self::XDEBUG_EXIT_BRANCH
                    ? 'exit'
                    : sprintf('b%d', $destBranchId);

                $edgeHit = isset($branch->out_hit[$edgeIndex]) && $branch->out_hit[$edgeIndex] > 0;
                $color   = $edgeHit ? $coveredBorder : $uncoveredEdge;

                $edgeKey = $destBranchId === self::XDEBUG_EXIT_BRANCH
                    ? $branchId . '-exit'
                    : $branchId . '-' . $destBranchId;

                $attrs = sprintf('color="%s"', $color);
                $attrs .= sprintf(', id="edge-%s"', $edgeKey);

                if (isset($edgePathClasses[$edgeKey])) {
                    $attrs .= sprintf(', class="%s"', implode(' ', $edgePathClasses[$edgeKey]));
                }

                $dot .= sprintf(
                    "  b%d -> %s [%s];\n",
                    $branchId,
                    $destNode,
                    $attrs,
                );
            }
        }

        $dot .= "}\n";

        return $dot;
    }

    /**
     * @param null|array<int, ProcessedPathCoverageData> $paths
     *
     * @return array<string, list<string>>
     */
    private function buildEdgePathClasses(ProcessedFunctionCoverageData $methodData, ?array $paths): array
    {
        $edgePathClasses = [];

        if ($paths === null) {
            return $edgePathClasses;
        }

        $pathIndex = 0;

        foreach ($paths as $path) {
            $branchIds = $path->path;

            for ($i = 0; $i < count($branchIds) - 1; $i++) {
                $edgeKey = $branchIds[$i] . '-' . $branchIds[$i + 1];

                if (!isset($edgePathClasses[$edgeKey])) {
                    $edgePathClasses[$edgeKey] = [];
                }

                $edgePathClasses[$edgeKey][] = 'path-' . $pathIndex;
            }

            $lastBranchId = $branchIds[count($branchIds) - 1];

            if (isset($methodData->branches[$lastBranchId])) {
                foreach ($methodData->branches[$lastBranchId]->out as $dest) {
                    if ($dest === self::XDEBUG_EXIT_BRANCH) {
                        $edgeKey = $lastBranchId . '-exit';

                        if (!isset($edgePathClasses[$edgeKey])) {
                            $edgePathClasses[$edgeKey] = [];
                        }

                        $edgePathClasses[$edgeKey][] = 'path-' . $pathIndex;
                    }
                }
            }

            $pathIndex++;
        }

        return $edgePathClasses;
    }

    private function dotToSvg(string $dot): string
    {
        if ($this->dotAvailable === false) {
            return '';
        }

        $descriptorSpec = [
            0 => ['pipe', 'r'],
            1 => ['pipe', 'w'],
            2 => ['pipe', 'w'],
        ];

        $process = @proc_open('dot -Tsvg', $descriptorSpec, $pipes);

        if ($process === false) {
            $this->dotAvailable = false;

            return '';
        }

        // Use non-blocking I/O to avoid deadlock when dot's output
        // buffer fills up before we finish writing the input
        stream_set_blocking($pipes[1], false);

        $written = 0;
        $length  = strlen($dot);
        $svg     = '';

        while ($written < $length) {
            $chunk = @fwrite($pipes[0], substr($dot, $written));

            if ($chunk === false) {
                break;
            }

            $written += $chunk;

            // Drain available output to prevent pipe buffer deadlock
            $svg .= stream_get_contents($pipes[1]);
        }

        fclose($pipes[0]);

        // Read remaining output
        stream_set_blocking($pipes[1], true);
        $svg .= stream_get_contents($pipes[1]);
        fclose($pipes[1]);
        fclose($pipes[2]);

        $exitCode = proc_close($process);

        if ($exitCode !== 0 || $svg === '') {
            $this->dotAvailable = false;

            return '';
        }

        $this->dotAvailable = true;

        // Strip XML declaration and DOCTYPE, keep only the <svg> element
        return preg_replace('/^.*?(<svg\b)/s', '$1', $svg);
    }
}
