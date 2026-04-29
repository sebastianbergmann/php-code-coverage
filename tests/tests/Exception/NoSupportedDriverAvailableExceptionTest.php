<?php declare(strict_types=1);
/*
 * This file is part of phpunit/php-code-coverage.
 *
 * (c) Sebastian Bergmann <sebastian@phpunit.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace SebastianBergmann\CodeCoverage;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Small;
use PHPUnit\Framework\TestCase;
use SebastianBergmann\CodeCoverage\Driver\Granularity;

#[CoversClass(NoSupportedDriverAvailableException::class)]
#[Small]
final class NoSupportedDriverAvailableExceptionTest extends TestCase
{
    /**
     * @return non-empty-list<array{0: Granularity, 1: string}>
     */
    public static function provider(): array
    {
        return [
            [Granularity::Line, 'No code coverage driver available that supports line coverage'],
            [Granularity::LineAndBranch, 'No code coverage driver available that supports line and branch coverage'],
            [Granularity::LineBranchAndPath, 'No code coverage driver available that supports line, branch, and path coverage'],
        ];
    }

    #[DataProvider('provider')]
    public function testHasMessageForGranularity(Granularity $granularity, string $expected): void
    {
        $this->assertSame($expected, (new NoSupportedDriverAvailableException($granularity))->getMessage());
    }
}
