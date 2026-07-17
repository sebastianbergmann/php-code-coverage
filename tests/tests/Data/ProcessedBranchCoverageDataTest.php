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

        $data->recordHit('testCaseId', 3);

        $this->assertSame(['testCaseId' => 3], $data->hit);
    }

    public function testRecordHitAccumulatesTraversalCountsForTestCase(): void
    {
        $data = new ProcessedBranchCoverageData(0, 5, 11, 12, [], [], []);

        $data->recordHit('testCaseId', 3);
        $data->recordHit('testCaseId', 2);

        $this->assertSame(['testCaseId' => 5], $data->hit);
    }

    public function testMergeReturnsSelfWhenOtherHasNoHits(): void
    {
        $data  = new ProcessedBranchCoverageData(0, 5, 11, 12, ['test1' => 1], [], []);
        $other = new ProcessedBranchCoverageData(0, 5, 11, 12, [], [], []);

        $this->assertSame($data, $data->merge($other));
    }

    public function testMergeCombinesHits(): void
    {
        $data  = new ProcessedBranchCoverageData(0, 5, 11, 12, ['test1' => 1], [], []);
        $other = new ProcessedBranchCoverageData(0, 5, 11, 12, ['test2' => 2], [], []);

        $merged = $data->merge($other);

        $this->assertNotSame($data, $merged);
        $this->assertSame(['test1' => 1, 'test2' => 2], $merged->hit);
    }

    public function testMergeKeepsHighestTraversalCountPerTestCase(): void
    {
        $data  = new ProcessedBranchCoverageData(0, 5, 11, 12, ['test1' => 2], [], []);
        $other = new ProcessedBranchCoverageData(0, 5, 11, 12, ['test1' => 2, 'test2' => 3], [], []);

        $merged = $data->merge($other);

        $this->assertSame(['test1' => 2, 'test2' => 3], $merged->hit);
    }
}
