<?php declare(strict_types=1);
/*
 * This file is part of phpunit/php-code-coverage.
 *
 * (c) Sebastian Bergmann <sebastian@phpunit.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace SebastianBergmann\CodeCoverage\Node;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Small;
use PHPUnit\Framework\TestCase;
use SebastianBergmann\CodeCoverage\Data\ProcessedFunctionCoverageData;

#[CoversClass(FileCoverageData::class)]
#[Small]
final class FileCoverageDataTest extends TestCase
{
    public function testHasLineCoverage(): void
    {
        $lineCoverage = [1 => [0 => 1], 2 => [], 3 => null];

        $this->assertSame($lineCoverage, (new FileCoverageData($lineCoverage, []))->lineCoverage);
    }

    public function testHasFunctionCoverage(): void
    {
        $functionCoverage = ['f' => new ProcessedFunctionCoverageData([], [])];

        $this->assertSame($functionCoverage, (new FileCoverageData([], $functionCoverage))->functionCoverage);
    }
}
