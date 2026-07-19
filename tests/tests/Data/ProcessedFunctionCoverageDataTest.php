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

#[CoversClass(ProcessedFunctionCoverageData::class)]
#[Small]
final class ProcessedFunctionCoverageDataTest extends TestCase
{
    public function testCanBeCreatedFromXdebugCoverage(): void
    {
        $data = ProcessedFunctionCoverageData::fromXdebugCoverage(
            [
                'branches' => [
                    0 => [
                        'op_start'   => 0,
                        'op_end'     => 5,
                        'line_start' => 11,
                        'line_end'   => 12,
                        'hit'        => 1,
                        'out'        => [0 => 6],
                        'out_hit'    => [0 => 1],
                    ],
                ],
                'paths' => [
                    0 => [
                        'path' => [0 => 0],
                        'hit'  => 1,
                    ],
                ],
            ],
        );

        $this->assertCount(1, $data->branches);
        $this->assertCount(1, $data->paths);
        $this->assertArrayHasKey(0, $data->branches);
        $this->assertArrayHasKey(0, $data->paths);
        $this->assertInstanceOf(ProcessedBranchCoverageData::class, $data->branches[0]);
        $this->assertInstanceOf(ProcessedPathCoverageData::class, $data->paths[0]);
        $this->assertSame(11, $data->branches[0]->line_start);
        $this->assertSame([0 => 0], $data->paths[0]->path);
    }

    public function testRecordBranchHitDelegatesToBranch(): void
    {
        $data = new ProcessedFunctionCoverageData(
            [0 => new ProcessedBranchCoverageData(0, 14, 20, 25, [], [], [])],
            [0 => new ProcessedPathCoverageData([0 => 0], [])],
        );

        $data->recordBranchHit(0, 0, 2);

        $this->assertArrayHasKey(0, $data->branches);
        $this->assertSame([0 => 2], $data->branches[0]->hit);
    }

    public function testRecordPathHitDelegatesToPath(): void
    {
        $data = new ProcessedFunctionCoverageData(
            [0 => new ProcessedBranchCoverageData(0, 14, 20, 25, [], [], [])],
            [0 => new ProcessedPathCoverageData([0 => 0], [])],
        );

        $data->recordPathHit(0, 0, 2);

        $this->assertArrayHasKey(0, $data->paths);
        $this->assertSame([0 => 2], $data->paths[0]->hit);
    }

    public function testMergeReturnsSelfWhenBranchesAndPathsAreIdentical(): void
    {
        $branch = new ProcessedBranchCoverageData(0, 14, 20, 25, [], [], []);
        $path   = new ProcessedPathCoverageData([0 => 0], []);

        $data = new ProcessedFunctionCoverageData(
            [0 => $branch],
            [0 => $path],
        );

        $result = $data->merge($data);

        $this->assertSame($data, $result);
    }

    public function testMergeCombinesAndAddsBranchesAndPaths(): void
    {
        $base = new ProcessedFunctionCoverageData(
            [0 => new ProcessedBranchCoverageData(0, 14, 20, 25, [0 => 1], [], [])],
            [0 => new ProcessedPathCoverageData([0 => 0], [0 => 1])],
        );

        $other = new ProcessedFunctionCoverageData(
            [
                0 => new ProcessedBranchCoverageData(0, 14, 20, 25, [1 => 2], [], []),
                1 => new ProcessedBranchCoverageData(15, 16, 26, 27, [2 => 3], [], []),
            ],
            [
                0 => new ProcessedPathCoverageData([0 => 0], [1 => 2]),
                1 => new ProcessedPathCoverageData([0 => 1], [2 => 3]),
            ],
        );

        $merged = $base->merge($other);

        $this->assertNotSame($base, $merged);
        $this->assertCount(2, $merged->branches);
        $this->assertCount(2, $merged->paths);
        $this->assertArrayHasKey(0, $merged->branches);
        $this->assertArrayHasKey(1, $merged->branches);
        $this->assertArrayHasKey(0, $merged->paths);
        $this->assertArrayHasKey(1, $merged->paths);
        $this->assertSame([0 => 1, 1 => 2], $merged->branches[0]->hit);
        $this->assertSame([2 => 3], $merged->branches[1]->hit);
        $this->assertSame([0 => 1, 1 => 2], $merged->paths[0]->hit);
        $this->assertSame([2 => 3], $merged->paths[1]->hit);
    }

    public function testRemapsTestIndexesOfBranchesAndPaths(): void
    {
        $data = new ProcessedFunctionCoverageData(
            [0 => new ProcessedBranchCoverageData(0, 14, 20, 25, [0 => 1], [], [])],
            [0 => new ProcessedPathCoverageData([0 => 0], [1 => 2])],
        );

        $remapped = $data->withRemappedTestIndexes([0 => 2, 1 => 3]);

        $this->assertNotSame($data, $remapped);
        $this->assertArrayHasKey(0, $remapped->branches);
        $this->assertArrayHasKey(0, $remapped->paths);
        $this->assertSame([2 => 1], $remapped->branches[0]->hit);
        $this->assertSame([3 => 2], $remapped->paths[0]->hit);
    }
}
