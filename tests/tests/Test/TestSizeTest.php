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
use PHPUnit\Framework\Attributes\Small;
use PHPUnit\Framework\TestCase;
use SebastianBergmann\CodeCoverage\Test\TestSize;

#[CoversClass(TestSize::class)]
#[Small]
final class TestSizeTest extends TestCase
{
    public function testCanBeUnknown(): void
    {
        $size = TestSize::Unknown;

        $this->assertTrue($size->isUnknown());
        $this->assertFalse($size->isKnown());
        $this->assertFalse($size->isSmall());
        $this->assertFalse($size->isMedium());
        $this->assertFalse($size->isLarge());
        $this->assertSame('unknown', $size->asString());
    }

    public function testCanBeSmall(): void
    {
        $size = TestSize::Small;

        $this->assertFalse($size->isUnknown());
        $this->assertTrue($size->isKnown());
        $this->assertTrue($size->isSmall());
        $this->assertFalse($size->isMedium());
        $this->assertFalse($size->isLarge());
        $this->assertSame('small', $size->asString());
    }

    public function testCanBeMedium(): void
    {
        $size = TestSize::Medium;

        $this->assertFalse($size->isUnknown());
        $this->assertTrue($size->isKnown());
        $this->assertFalse($size->isSmall());
        $this->assertTrue($size->isMedium());
        $this->assertFalse($size->isLarge());
        $this->assertSame('medium', $size->asString());
    }

    public function testCanBeLarge(): void
    {
        $size = TestSize::Large;

        $this->assertFalse($size->isUnknown());
        $this->assertTrue($size->isKnown());
        $this->assertFalse($size->isSmall());
        $this->assertFalse($size->isMedium());
        $this->assertTrue($size->isLarge());
        $this->assertSame('large', $size->asString());
    }

    public function testCanBeCompared(): void
    {
        $this->assertFalse(TestSize::Small->isGreaterThan(TestSize::Small));
        $this->assertFalse(TestSize::Medium->isGreaterThan(TestSize::Large));
        $this->assertTrue(TestSize::Medium->isGreaterThan(TestSize::Small));
        $this->assertFalse(TestSize::Large->isGreaterThan(TestSize::Large));
        $this->assertTrue(TestSize::Large->isGreaterThan(TestSize::Small));
        $this->assertTrue(TestSize::Large->isGreaterThan(TestSize::Medium));
    }
}
