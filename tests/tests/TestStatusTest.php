<?php declare(strict_types=1);
/*
 * This file is part of phpunit/php-code-coverage.
 *
 * (c) Sebastian Bergmann <sebastian@phpunit.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace SebastianBergmann\CodeCoverage\Test\TestStatus;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Small;
use PHPUnit\Framework\TestCase;

#[CoversClass(TestStatus::class)]
#[CoversClass(Known::class)]
#[CoversClass(Unknown::class)]
#[CoversClass(Success::class)]
#[CoversClass(Failure::class)]
#[Small]
final class TestStatusTest extends TestCase
{
    public function testCanBeUnknown(): void
    {
        $status = TestStatus::unknown();

        $this->assertTrue($status->isUnknown());
        $this->assertFalse($status->isKnown());
        $this->assertFalse($status->isSuccess());
        $this->assertFalse($status->isFailure());
        $this->assertSame('unknown', $status->asString());
    }

    public function testCanBeSuccess(): void
    {
        $status = TestStatus::success();

        $this->assertFalse($status->isUnknown());
        $this->assertTrue($status->isKnown());
        $this->assertTrue($status->isSuccess());
        $this->assertFalse($status->isFailure());
        $this->assertSame('success', $status->asString());
    }

    public function testCanBeFailure(): void
    {
        $status = TestStatus::failure();

        $this->assertFalse($status->isUnknown());
        $this->assertTrue($status->isKnown());
        $this->assertFalse($status->isSuccess());
        $this->assertTrue($status->isFailure());
        $this->assertSame('failure', $status->asString());
    }
}
