<?php declare(strict_types=1);
/*
 * This file is part of phpunit/php-code-coverage.
 *
 * (c) Sebastian Bergmann <sebastian@phpunit.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace SebastianBergmann\CodeCoverage\Test\Target;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversClassesThatExtendClass;
use PHPUnit\Framework\Attributes\Small;
use PHPUnit\Framework\TestCase;

#[CoversClass(ValidationResult::class)]
#[CoversClassesThatExtendClass(ValidationResult::class)]
#[Small]
final class ValidationResultTest extends TestCase
{
    public function testCanBeSuccess(): void
    {
        $this->assertTrue(ValidationResult::success()->isSuccess());
        $this->assertFalse(ValidationResult::success()->isFailure());
    }

    public function testCanBeFailure(): void
    {
        $message = 'message';

        $this->assertTrue(ValidationResult::failure($message)->isFailure());
        $this->assertFalse(ValidationResult::failure($message)->isSuccess());
        $this->assertSame($message, ValidationResult::failure($message)->message());
    }
}
