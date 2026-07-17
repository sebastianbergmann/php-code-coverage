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

#[CoversClass(ProcessedPathCoverageData::class)]
#[Small]
final class ProcessedPathCoverageDataTest extends TestCase
{
    public function testCanBeCreatedFromXdebugCoverage(): void
    {
        $data = ProcessedPathCoverageData::fromXdebugCoverage(
            [
                'path' => [0 => 0, 1 => 8],
                'hit'  => 1,
            ],
        );

        $this->assertSame([0 => 0, 1 => 8], $data->path);
        $this->assertSame([], $data->hit);
    }

    public function testRecordHitRecordsTraversalCountForTestCase(): void
    {
        $data = new ProcessedPathCoverageData([0 => 0], []);

        $data->recordHit('testCaseId', 3);

        $this->assertSame(['testCaseId' => 3], $data->hit);
    }

    public function testRecordHitAccumulatesTraversalCountsForTestCase(): void
    {
        $data = new ProcessedPathCoverageData([0 => 0], []);

        $data->recordHit('testCaseId', 3);
        $data->recordHit('testCaseId', 2);

        $this->assertSame(['testCaseId' => 5], $data->hit);
    }

    public function testMergeReturnsSelfWhenOtherHasNoHits(): void
    {
        $data  = new ProcessedPathCoverageData([0 => 0], ['test1' => 1]);
        $other = new ProcessedPathCoverageData([0 => 0], []);

        $this->assertSame($data, $data->merge($other));
    }

    public function testMergeCombinesHits(): void
    {
        $data  = new ProcessedPathCoverageData([0 => 0], ['test1' => 1]);
        $other = new ProcessedPathCoverageData([0 => 0], ['test2' => 2]);

        $merged = $data->merge($other);

        $this->assertNotSame($data, $merged);
        $this->assertSame(['test1' => 1, 'test2' => 2], $merged->hit);
    }

    public function testMergeKeepsHighestTraversalCountPerTestCase(): void
    {
        $data  = new ProcessedPathCoverageData([0 => 0], ['test1' => 2]);
        $other = new ProcessedPathCoverageData([0 => 0], ['test1' => 2, 'test2' => 3]);

        $merged = $data->merge($other);

        $this->assertSame(['test1' => 2, 'test2' => 3], $merged->hit);
    }
}
