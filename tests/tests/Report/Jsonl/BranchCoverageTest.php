<?php declare(strict_types=1);
/*
 * This file is part of phpunit/php-code-coverage.
 *
 * (c) Sebastian Bergmann <sebastian@phpunit.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace SebastianBergmann\CodeCoverage\Report\Jsonl;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Small;
use PHPUnit\Framework\TestCase;
use SebastianBergmann\CodeCoverage\Data\ProcessedBranchCoverageData;

#[CoversClass(BranchCoverage::class)]
#[Small]
final class BranchCoverageTest extends TestCase
{
    public function testCountsBlocksThatWereEntered(): void
    {
        $branchCoverage = BranchCoverage::fromBranches([
            $this->branch(10, 10, true, [1 => 1]),
            $this->branch(11, 11, true, [1 => 1]),
            $this->branch(12, 12, false, []),
        ]);

        $this->assertSame(2, $branchCoverage->executed);
        $this->assertSame(3, $branchCoverage->total);
    }

    public function testReportsEveryLineOfABlockThatWasNeverEntered(): void
    {
        $branchCoverage = BranchCoverage::fromBranches([
            $this->branch(10, 10, true, [1 => 1]),
            $this->branch(12, 14, false, []),
        ]);

        $this->assertSame([12, 13, 14], $branchCoverage->uncoveredLines);
    }

    /**
     * The case block counts alone cannot express: for "if ($x) { a(); } b();"
     * every block is entered even when only the true path is ever taken,
     * because b() runs either way. Only the never-taken edge shows it.
     */
    public function testReportsABlockThatWasEnteredButWhoseEdgeWasNeverTaken(): void
    {
        $branchCoverage = BranchCoverage::fromBranches([
            $this->branch(34, 34, true, [1 => 1, 2 => 0]),
            $this->branch(35, 35, true, [1 => 1]),
            $this->branch(36, 36, true, [1 => 1]),
        ]);

        $this->assertSame(3, $branchCoverage->executed);
        $this->assertSame(3, $branchCoverage->total);
        $this->assertSame([34], $branchCoverage->uncoveredLines);
    }

    public function testReportsTheLineABlockEndsOnForANeverTakenEdge(): void
    {
        $branchCoverage = BranchCoverage::fromBranches([
            $this->branch(34, 37, true, [1 => 1, 2 => 0]),
            $this->branch(38, 38, true, [1 => 1]),
        ]);

        $this->assertSame([37], $branchCoverage->uncoveredLines);
    }

    public function testReportsABlockWithSeveralNeverTakenEdgesOnlyOnce(): void
    {
        $branchCoverage = BranchCoverage::fromBranches([
            $this->branch(34, 34, true, [1 => 0, 2 => 0, 3 => 0]),
            $this->branch(35, 35, true, [1 => 1]),
        ]);

        $this->assertSame([34], $branchCoverage->uncoveredLines);
    }

    /**
     * A block with a single outgoing edge is not a decision, so an edge out of
     * it that was never taken means the block left the function rather than
     * that a branch was missed.
     */
    public function testDoesNotReportANeverTakenEdgeOutOfABlockThatDoesNotBranch(): void
    {
        $branchCoverage = BranchCoverage::fromBranches([
            $this->branch(34, 34, true, [1 => 0]),
        ]);

        $this->assertSame([], $branchCoverage->uncoveredLines);
    }

    public function testReportsNothingWhenEveryBlockAndEveryEdgeWasTaken(): void
    {
        $branchCoverage = BranchCoverage::fromBranches([
            $this->branch(34, 34, true, [1 => 1, 2 => 1]),
            $this->branch(35, 35, true, [1 => 1]),
        ]);

        $this->assertSame(2, $branchCoverage->executed);
        $this->assertSame(2, $branchCoverage->total);
        $this->assertSame([], $branchCoverage->uncoveredLines);
    }

    /**
     * @param array<int, int> $outHit edge index to the number of times the edge was taken
     */
    private function branch(int $lineStart, int $lineEnd, bool $hit, array $outHit): ProcessedBranchCoverageData
    {
        $hits = [];

        if ($hit) {
            $hits = [0 => 1];
        }

        $out = [];

        foreach ($outHit as $edge => $taken) {
            $out[$edge] = $edge + 1;
        }

        return new ProcessedBranchCoverageData(0, 0, $lineStart, $lineEnd, $hits, $out, $outHit);
    }
}
