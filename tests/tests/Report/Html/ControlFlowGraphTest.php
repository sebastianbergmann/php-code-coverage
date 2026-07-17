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

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Small;
use PHPUnit\Framework\TestCase;
use SebastianBergmann\CodeCoverage\Data\ProcessedBranchCoverageData;
use SebastianBergmann\CodeCoverage\Data\ProcessedFunctionCoverageData;
use SebastianBergmann\CodeCoverage\Data\ProcessedPathCoverageData;

#[CoversClass(ControlFlowGraph::class)]
#[Small]
final class ControlFlowGraphTest extends TestCase
{
    public function testGeneratesDotWithCoverageClassesForNodesAndEdges(): void
    {
        $dot = (new ControlFlowGraph)->generateDot(
            $this->methodData(),
            $this->methodData()->paths,
        );

        $this->assertStringContainsString('digraph {', $dot);
        $this->assertStringContainsString('bgcolor=transparent;', $dot);
        $this->assertStringContainsString('entry [label="entry", shape=oval, class="terminal"];', $dot);
        $this->assertStringContainsString('exit [label="exit", shape=oval, class="terminal"];', $dot);

        $this->assertStringContainsString('b0 [label="L10-L12", class="covered"];', $dot);
        $this->assertStringContainsString('b5 [label="L13", class="covered"];', $dot);
        $this->assertStringContainsString('b9 [label="L14-L15", class="uncovered"];', $dot);

        $this->assertStringContainsString('entry -> b0;', $dot);
        $this->assertStringContainsString('b0 -> b5 [id="edge-0-5", class="covered path-0"];', $dot);
        $this->assertStringContainsString('b0 -> b9 [id="edge-0-9", class="uncovered path-1"];', $dot);
        $this->assertStringContainsString('b5 -> exit [id="edge-5-exit", class="covered path-0"];', $dot);
        $this->assertStringContainsString('b9 -> exit [id="edge-9-exit", class="uncovered path-1"];', $dot);

        $this->assertStringNotContainsString('fillcolor', $dot);
        $this->assertStringNotContainsString('#', $dot);
    }

    public function testGeneratesDotWithoutPathClassesWhenNoPathsAreProvided(): void
    {
        $dot = (new ControlFlowGraph)->generateDot($this->methodData());

        $this->assertStringContainsString('b0 -> b5 [id="edge-0-5", class="covered"];', $dot);
        $this->assertStringNotContainsString('path-0', $dot);
    }

    public function testRendersNoSvgWhenDotIsNotAvailable(): void
    {
        $controlFlowGraph = new ControlFlowGraph('binary-that-does-not-exist');

        $this->assertSame('', $controlFlowGraph->renderSvg($this->methodData()));

        // the failure is remembered, subsequent calls do not spawn a process
        $this->assertSame('', $controlFlowGraph->renderSvg($this->methodData()));
    }

    private function methodData(): ProcessedFunctionCoverageData
    {
        return new ProcessedFunctionCoverageData(
            [
                0 => new ProcessedBranchCoverageData(0, 4, 10, 12, ['test' => 1], [0 => 5, 1 => 9], [0 => 1, 1 => 0]),
                5 => new ProcessedBranchCoverageData(5, 8, 13, 13, ['test' => 1], [0 => ControlFlowGraph::XDEBUG_EXIT_BRANCH], [0 => 1]),
                9 => new ProcessedBranchCoverageData(9, 12, 15, 14, [], [0 => ControlFlowGraph::XDEBUG_EXIT_BRANCH], [0 => 0]),
            ],
            [
                0 => new ProcessedPathCoverageData([0, 5], ['test' => 1]),
                1 => new ProcessedPathCoverageData([0, 9], []),
            ],
        );
    }
}
