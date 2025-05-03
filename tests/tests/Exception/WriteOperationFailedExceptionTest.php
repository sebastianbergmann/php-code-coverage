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
use PHPUnit\Framework\Attributes\Small;
use PHPUnit\Framework\TestCase;

#[CoversClass(WriteOperationFailedException::class)]
#[Small]
final class WriteOperationFailedExceptionTest extends TestCase
{
    public function testHasMessage(): void
    {
        $e = new WriteOperationFailedException('/path');

        $this->assertSame('Cannot write to "/path"', $e->getMessage());
    }
}
