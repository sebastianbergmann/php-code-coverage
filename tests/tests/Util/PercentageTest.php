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
use PHPUnit\Framework\Attributes\Small;
use PHPUnit\Framework\TestCase;

#[CoversClass(Percentage::class)]
#[Small]
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

    public function testCanBeRepresentedAsStringWithoutPercentSign(): void
    {
        $this->assertSame(
            '50.00',
            Percentage::fromFractionAndTotal(1, 2)->asStringWithoutPercentSign(),
        );

        $this->assertSame(
            '',
            Percentage::fromFractionAndTotal(0, 0)->asStringWithoutPercentSign(),
        );
    }

    public function testCanBeRepresentedAsFixedWidthString(): void
    {
        $this->assertSame(
            ' 50.00%',
            Percentage::fromFractionAndTotal(1, 2)->asFixedWidthString(),
        );
    }

    public function testStringRepresentationRoundsTowardsZero(): void
    {
        $this->assertSame(
            '99.99%',
            Percentage::fromFractionAndTotal(99999, 100000)->asString(),
        );

        $this->assertSame(
            '66.66%',
            Percentage::fromFractionAndTotal(2, 3)->asString(),
        );

        $this->assertSame(
            '99.99%',
            Percentage::fromFractionAndTotal(9999, 10000)->asString(),
        );

        $this->assertSame(
            '100.00%',
            Percentage::fromFractionAndTotal(2, 2)->asString(),
        );

        $this->assertSame(
            ' 99.99%',
            Percentage::fromFractionAndTotal(99999, 100000)->asFixedWidthString(),
        );

        $this->assertSame(
            '99.99',
            Percentage::fromFractionAndTotal(99999, 100000)->asStringWithoutPercentSign(),
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
