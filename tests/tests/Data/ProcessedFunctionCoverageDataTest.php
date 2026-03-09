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
use PHPUnit\Framework\TestCase;

#[CoversClass(ProcessedFunctionCoverageData::class)]
final class ProcessedFunctionCoverageDataTest extends TestCase
{
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
}
