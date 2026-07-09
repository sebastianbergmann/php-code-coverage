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
    /**
     * Branch identifier used by Xdebug for the exit of a function (XDEBUG_BRANCH_EXIT in xdebug_branch_info.h).
     */
    public const int XDEBUG_EXIT_BRANCH = 2147483645;
    private ?bool $dotAvailable         = null;
    private readonly string $dotBinary;

    public function __construct(string $dotBinary = 'dot')
    {
        $this->dotBinary = $dotBinary;
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
     * Generates the graph in Graphviz DOT format.
     *
     * Nodes and edges carry "covered" / "uncovered" classes instead of color
     * literals; the report's stylesheet maps them to the configured color
     * scheme in both light mode and dark mode.
     *
     * @param null|array<int, ProcessedPathCoverageData> $paths
     */
    public function generateDot(ProcessedFunctionCoverageData $methodData, ?array $paths = null): string
    {
        $dot = "digraph {\n";
        $dot .= "  rankdir=TB;\n";
        $dot .= "  bgcolor=transparent;\n";
        $dot .= '  node [shape=box, style=filled, fontname="sans-serif", fontsize=11];' . "\n";
        $dot .= '  entry [label="entry", shape=oval, class="terminal"];' . "\n";

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

            $dot .= sprintf(
                '  b%d [label="%s", class="%s"];' . "\n",
                $branchId,
                $label,
                $branch->hit !== [] ? 'covered' : 'uncovered',
            );
        }

        if ($hasExit) {
            $dot .= '  exit [label="exit", shape=oval, class="terminal"];' . "\n";
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

                $edgeKey = $destBranchId === self::XDEBUG_EXIT_BRANCH
                    ? $branchId . '-exit'
                    : $branchId . '-' . $destBranchId;

                $classes = [$edgeHit ? 'covered' : 'uncovered'];

                if (isset($edgePathClasses[$edgeKey])) {
                    $classes = [...$classes, ...$edgePathClasses[$edgeKey]];
                }

                $dot .= sprintf(
                    '  b%d -> %s [id="edge-%s", class="%s"];' . "\n",
                    $branchId,
                    $destNode,
                    $edgeKey,
                    implode(' ', $classes),
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
            $previousBranchId = null;
            $lastBranchId     = null;

            foreach ($path->path as $branchId) {
                if ($previousBranchId !== null) {
                    $edgeKey = $previousBranchId . '-' . $branchId;

                    if (!isset($edgePathClasses[$edgeKey])) {
                        $edgePathClasses[$edgeKey] = [];
                    }

                    $edgePathClasses[$edgeKey][] = 'path-' . $pathIndex;
                }

                $previousBranchId = $branchId;
                $lastBranchId     = $branchId;
            }

            if ($lastBranchId !== null && isset($methodData->branches[$lastBranchId])) {
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

        $process = @proc_open($this->dotBinary . ' -Tsvg', $descriptorSpec, $pipes);

        if ($process === false || !isset($pipes[0], $pipes[1], $pipes[2])) {
            // @codeCoverageIgnoreStart
            $this->dotAvailable = false;

            return '';
            // @codeCoverageIgnoreEnd
        }

        // Use non-blocking I/O to avoid deadlock when dot's output
        // buffer fills up before we finish writing the input
        stream_set_blocking($pipes[1], false);

        $written = 0;
        $length  = strlen($dot);
        $svg     = '';

        while ($written < $length) {
            $chunk = @fwrite($pipes[0], substr($dot, $written));

            if ($chunk === false || $chunk === 0) {
                // @codeCoverageIgnoreStart
                break;
                // @codeCoverageIgnoreEnd
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
        return preg_replace('/^.*?(<svg\b)/s', '$1', $svg) ?? '';
    }
}
