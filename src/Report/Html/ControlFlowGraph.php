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

use const PHP_OS_FAMILY;
use function assert;
use function fclose;
use function fflush;
use function fread;
use function fwrite;
use function implode;
use function preg_replace;
use function proc_close;
use function proc_open;
use function sprintf;
use function str_contains;
use function stream_select;
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

    private const int DOT_TIMEOUT_SECONDS = 30;
    private ?bool $dotAvailable           = null;

    /** @var null|resource */
    private $process;

    /** @var array<int, resource> */
    private array $pipes = [];
    private readonly string $dotBinary;

    public function __construct(string $dotBinary = 'dot')
    {
        $this->dotBinary = $dotBinary;
    }

    public function __destruct()
    {
        $this->stopDot();
    }

    /**
     * @param null|array<int, ProcessedPathCoverageData> $paths
     */
    public function renderSvg(string $methodName, ProcessedFunctionCoverageData $methodData, ?array $paths = null): string
    {
        $dot = $this->generateDot($methodName, $methodData, $paths);

        return $this->dotToSvg($dot, $this->id($methodName));
    }

    /**
     * Generates the graph in Graphviz DOT format.
     *
     * Nodes and edges carry "covered" / "uncovered" classes instead of color
     * literals; the report's stylesheet maps them to the configured color
     * scheme in both light mode and dark mode.
     *
     * The graph carries an identifier derived from the name of the method,
     * which dot also uses as the prefix of the identifiers it generates for
     * the nodes and which is the prefix of the identifiers of the edges.
     * All graphs of a report and all of their elements therefore have
     * distinct identifiers.
     *
     * @param null|array<int, ProcessedPathCoverageData> $paths
     */
    public function generateDot(string $methodName, ProcessedFunctionCoverageData $methodData, ?array $paths = null): string
    {
        $id = $this->id($methodName);

        $dot = "digraph {\n";
        $dot .= sprintf("  id=\"%s\";\n", $id);
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

            $label = $branch->line_start === $branch->line_end
                ? sprintf('L%d', $branch->line_start)
                : sprintf('L%d-L%d', $branch->line_start, $branch->line_end);

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
                    '  b%d -> %s [id="%s_edge-%s", class="%s"];' . "\n",
                    $branchId,
                    $destNode,
                    $id,
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

    /**
     * dot spends most of its time initializing itself (plugins, font
     * configuration), not laying out a graph. All graphs of a report are
     * therefore piped through a single dot process, which emits the SVG of
     * each graph as soon as that graph has been read.
     */
    /**
     * The identifier of the graph: the name of the method with every
     * character that is not a letter, a digit, or an underscore replaced by
     * a hyphen, so that it is usable as an HTML identifier as well as in a
     * CSS selector.
     *
     * @return non-empty-string
     */
    private function id(string $methodName): string
    {
        return 'cfg-' . preg_replace('/[^A-Za-z0-9_]/', '-', $methodName);
    }

    /**
     * @param non-empty-string $id
     */
    private function dotToSvg(string $dot, string $id): string
    {
        if ($this->dotAvailable === false) {
            return '';
        }

        if ($this->process === null && !$this->startDot()) {
            return '';
        }

        assert(isset($this->pipes[0], $this->pipes[1]));

        $written = 0;
        $length  = strlen($dot);

        while ($written < $length) {
            $chunk = @fwrite($this->pipes[0], substr($dot, $written));

            if ($chunk === false || $chunk === 0) {
                // @codeCoverageIgnoreStart
                $this->stopDot();

                return '';
                // @codeCoverageIgnoreEnd
            }

            $written += $chunk;
        }

        fflush($this->pipes[0]);

        $svg = '';

        while (!str_contains($svg, '</svg>')) {
            $read   = [$this->pipes[1]];
            $write  = null;
            $except = null;

            if (@stream_select($read, $write, $except, self::DOT_TIMEOUT_SECONDS) !== 1) {
                // @codeCoverageIgnoreStart
                $this->stopDot();

                return '';
                // @codeCoverageIgnoreEnd
            }

            $chunk = fread($this->pipes[1], 65536);

            if ($chunk === false || $chunk === '') {
                // dot has exited, for instance because it could not parse the graph
                $this->stopDot();

                return '';
            }

            $svg .= $chunk;
        }

        $this->dotAvailable = true;

        // Strip XML declaration and DOCTYPE, keep only the <svg> element
        $svg = preg_replace('/^.*?(<svg\b)/s', '$1', $svg) ?? '';

        // dot prefixes the identifier of every graph but the first one that
        // it reads from its input with the page number
        return preg_replace('/(<g id=")[^"]*(" class="graph")/', '${1}' . $id . '$2', $svg, 1) ?? '';
    }

    private function startDot(): bool
    {
        $descriptorSpec = [
            0 => ['pipe', 'r'],
            1 => ['pipe', 'w'],
            2 => ['file', PHP_OS_FAMILY === 'Windows' ? 'NUL' : '/dev/null', 'w'],
        ];

        $process = @proc_open($this->dotBinary . ' -Tsvg', $descriptorSpec, $pipes);

        if ($process === false || !isset($pipes[0], $pipes[1])) {
            // @codeCoverageIgnoreStart
            $this->dotAvailable = false;

            return false;
            // @codeCoverageIgnoreEnd
        }

        $this->process = $process;
        $this->pipes   = $pipes;

        return true;
    }

    private function stopDot(): void
    {
        if ($this->process === null) {
            return;
        }

        foreach ($this->pipes as $pipe) {
            @fclose($pipe);
        }

        proc_close($this->process);

        $this->process      = null;
        $this->pipes        = [];
        $this->dotAvailable = false;
    }
}
