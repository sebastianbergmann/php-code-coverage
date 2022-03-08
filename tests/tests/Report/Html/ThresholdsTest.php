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
use SebastianBergmann\CodeCoverage\InvalidArgumentException;
use SebastianBergmann\CodeCoverage\Report\Thresholds;

#[CoversClass(Thresholds::class)]
#[Small]
final class ThresholdsTest extends TestCase
{
    public function testCanBeCreatedFromDefaults(): void
    {
        $thresholds = Thresholds::default();

        $this->assertSame(50, $thresholds->lowUpperBound());
        $this->assertSame(90, $thresholds->highLowerBound());
    }

    public function testCanBeCreatedFromValidCustomValues(): void
    {
        $thresholds = Thresholds::from(60, 95);

        $this->assertSame(60, $thresholds->lowUpperBound());
        $this->assertSame(95, $thresholds->highLowerBound());
    }

    public function testCannotBeCreatedFromInvalidValues(): void
    {
        $this->expectException(InvalidArgumentException::class);

        Thresholds::from(90, 50);
    }
}
