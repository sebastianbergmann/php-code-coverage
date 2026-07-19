<?php declare(strict_types=1);
/*
 * This file is part of phpunit/php-code-coverage.
 *
 * (c) Sebastian Bergmann <sebastian@phpunit.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace SebastianBergmann\CodeCoverage\Data;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Small;
use PHPUnit\Framework\TestCase;

#[CoversClass(ProcessedBranchCoverageData::class)]
#[Small]
final class ProcessedBranchCoverageDataTest extends TestCase
{
    public function testCanBeCreatedFromXdebugCoverage(): void
    {
        $data = ProcessedBranchCoverageData::fromXdebugCoverage(
            [
                'op_start'   => 0,
                'op_end'     => 5,
                'line_start' => 11,
                'line_end'   => 12,
                'hit'        => 1,
                'out'        => [0 => 6, 1 => 8],
                'out_hit'    => [0 => 0, 1 => 1],
            ],
        );

        $this->assertSame(0, $data->op_start);
        $this->assertSame(5, $data->op_end);
        $this->assertSame(11, $data->line_start);
        $this->assertSame(12, $data->line_end);
        $this->assertSame([], $data->hit);
        $this->assertSame([0 => 6, 1 => 8], $data->out);
        $this->assertSame([0 => 0, 1 => 1], $data->out_hit);
    }

    public function testRecordHitRecordsTraversalCountForTestCase(): void
    {
        $data = new ProcessedBranchCoverageData(0, 5, 11, 12, [], [], []);

        $data->recordHit(0, 3);

        $this->assertSame([0 => 3], $data->hit);
    }

    public function testRecordHitAccumulatesTraversalCountsForTestCase(): void
    {
        $data = new ProcessedBranchCoverageData(0, 5, 11, 12, [], [], []);

        $data->recordHit(0, 3);
        $data->recordHit(0, 2);

        $this->assertSame([0 => 5], $data->hit);
    }

    public function testMergeReturnsSelfWhenOtherHasNoHits(): void
    {
        $data  = new ProcessedBranchCoverageData(0, 5, 11, 12, [0 => 1], [], []);
        $other = new ProcessedBranchCoverageData(0, 5, 11, 12, [], [], []);

        $this->assertSame($data, $data->merge($other));
    }

    public function testMergeCombinesHits(): void
    {
        $data  = new ProcessedBranchCoverageData(0, 5, 11, 12, [0 => 1], [], []);
        $other = new ProcessedBranchCoverageData(0, 5, 11, 12, [1 => 2], [], []);

        $merged = $data->merge($other);

        $this->assertNotSame($data, $merged);
        $this->assertSame([0 => 1, 1 => 2], $merged->hit);
    }

    public function testMergeKeepsHighestTraversalCountPerTestCase(): void
    {
        $data  = new ProcessedBranchCoverageData(0, 5, 11, 12, [0 => 2], [], []);
        $other = new ProcessedBranchCoverageData(0, 5, 11, 12, [0 => 2, 1 => 3], [], []);

        $merged = $data->merge($other);

        $this->assertSame([0 => 2, 1 => 3], $merged->hit);
    }

    public function testRemappingTestIndexesReturnsSelfWhenThereAreNoHits(): void
    {
        $data = new ProcessedBranchCoverageData(0, 5, 11, 12, [], [], []);

        $this->assertSame($data, $data->withRemappedTestIndexes([0 => 1]));
    }

    public function testRemapsTestIndexesOfHits(): void
    {
        $data = new ProcessedBranchCoverageData(0, 5, 11, 12, [0 => 1, 1 => 2], [0 => 6], [0 => 1]);

        $remapped = $data->withRemappedTestIndexes([0 => 2, 1 => 3]);

        $this->assertNotSame($data, $remapped);
        $this->assertSame([2 => 1, 3 => 2], $remapped->hit);
        $this->assertSame(0, $remapped->op_start);
        $this->assertSame(5, $remapped->op_end);
        $this->assertSame(11, $remapped->line_start);
        $this->assertSame(12, $remapped->line_end);
        $this->assertSame([0 => 6], $remapped->out);
        $this->assertSame([0 => 1], $remapped->out_hit);
    }

    public function testKeepsTestIndexesOfHitsThatAreNotRemapped(): void
    {
        $data = new ProcessedBranchCoverageData(0, 5, 11, 12, [0 => 1, 1 => 2], [], []);

        $remapped = $data->withRemappedTestIndexes([0 => 2]);

        $this->assertSame([2 => 1, 1 => 2], $remapped->hit);
    }
}
