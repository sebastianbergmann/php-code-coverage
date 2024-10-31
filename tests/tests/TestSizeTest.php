<?php declare(strict_types=1);
/*
 * This file is part of phpunit/php-code-coverage.
 *
 * (c) Sebastian Bergmann <sebastian@phpunit.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace SebastianBergmann\CodeCoverage\Test\TestSize;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(TestSize::class)]
#[CoversClass(Unknown::class)]
#[CoversClass(Known::class)]
#[CoversClass(Small::class)]
#[CoversClass(Medium::class)]
#[CoversClass(Large::class)]
#[\PHPUnit\Framework\Attributes\Small]
final class TestSizeTest extends TestCase
{
    public function testCanBeUnknown(): void
    {
        $size = TestSize::unknown();

        $this->assertTrue($size->isUnknown());
        $this->assertFalse($size->isKnown());
        $this->assertFalse($size->isSmall());
        $this->assertFalse($size->isMedium());
        $this->assertFalse($size->isLarge());
        $this->assertSame('unknown', $size->asString());
    }

    public function testCanBeSmall(): void
    {
        $size = TestSize::small();

        $this->assertFalse($size->isUnknown());
        $this->assertTrue($size->isKnown());
        $this->assertTrue($size->isSmall());
        $this->assertFalse($size->isMedium());
        $this->assertFalse($size->isLarge());
        $this->assertSame('small', $size->asString());
    }

    public function testCanBeMedium(): void
    {
        $size = TestSize::medium();

        $this->assertFalse($size->isUnknown());
        $this->assertTrue($size->isKnown());
        $this->assertFalse($size->isSmall());
        $this->assertTrue($size->isMedium());
        $this->assertFalse($size->isLarge());
        $this->assertSame('medium', $size->asString());
    }

    public function testCanBeLarge(): void
    {
        $size = TestSize::large();

        $this->assertFalse($size->isUnknown());
        $this->assertTrue($size->isKnown());
        $this->assertFalse($size->isSmall());
        $this->assertFalse($size->isMedium());
        $this->assertTrue($size->isLarge());
        $this->assertSame('large', $size->asString());
    }

    public function testCanBeCompared(): void
    {
        $this->assertFalse(TestSize::small()->isGreaterThan(TestSize::small()));
        $this->assertFalse(TestSize::medium()->isGreaterThan(TestSize::large()));
        $this->assertTrue(TestSize::medium()->isGreaterThan(TestSize::small()));
        $this->assertFalse(TestSize::large()->isGreaterThan(TestSize::large()));
        $this->assertTrue(TestSize::large()->isGreaterThan(TestSize::small()));
        $this->assertTrue(TestSize::large()->isGreaterThan(TestSize::medium()));
    }
}
