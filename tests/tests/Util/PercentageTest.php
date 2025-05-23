<?php declare(strict_types=1);
/*
 * This file is part of phpunit/php-code-coverage.
 *
 * (c) Sebastian Bergmann <sebastian@phpunit.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace SebastianBergmann\CodeCoverage\Util;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(Percentage::class)]
final class PercentageTest extends TestCase
{
    public function testCanBeRepresentedAsFloat(): void
    {
        $this->assertSame(
            50.0,
            Percentage::fromFractionAndTotal(1, 2)->asFloat(),
        );
    }

    public function testCanBeRepresentedAsString(): void
    {
        $this->assertSame(
            '50.00%',
            Percentage::fromFractionAndTotal(1, 2)->asString(),
        );
    }

    public function testCanBeRepresentedAsFixedWidthString(): void
    {
        $this->assertSame(
            ' 50.00%',
            Percentage::fromFractionAndTotal(1, 2)->asFixedWidthString(),
        );
    }

    public function testRepresentsTotalOfZeroAsEmptyString(): void
    {
        $this->assertSame(
            '',
            Percentage::fromFractionAndTotal(0, 0)->asString(),
        );

        $this->assertSame(
            '',
            Percentage::fromFractionAndTotal(0, 0)->asFixedWidthString(),
        );
    }

    public function testRepresentsTotalOfZeroAs100PercentFloat(): void
    {
        $this->assertSame(
            100.0,
            Percentage::fromFractionAndTotal(0, 0)->asFloat(),
        );
    }
}
