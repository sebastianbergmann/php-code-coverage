<?php declare(strict_types=1);
/*
 * This file is part of phpunit/php-code-coverage.
 *
 * (c) Sebastian Bergmann <sebastian@phpunit.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace SebastianBergmann\CodeCoverage\Test;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Small;
use PHPUnit\Framework\TestCase;

#[CoversClass(TestSizes::class)]
#[Small]
final class TestSizesTest extends TestCase
{
    public function testMapsSmallToItsBit(): void
    {
        $this->assertSame(TestSizes::SMALL, TestSizes::bitFor('small'));
    }

    public function testMapsMediumToItsBit(): void
    {
        $this->assertSame(TestSizes::MEDIUM, TestSizes::bitFor('medium'));
    }

    public function testMapsLargeToItsBit(): void
    {
        $this->assertSame(TestSizes::LARGE, TestSizes::bitFor('large'));
    }

    public function testMapsUnknownSizeToZero(): void
    {
        $this->assertSame(0, TestSizes::bitFor('unknown'));
    }
}
