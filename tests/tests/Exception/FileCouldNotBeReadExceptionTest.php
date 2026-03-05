<?php declare(strict_types=1);
/*
 * This file is part of phpunit/php-code-coverage.
 *
 * (c) Sebastian Bergmann <sebastian@phpunit.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace SebastianBergmann\CodeCoverage\Serialization;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Small;
use PHPUnit\Framework\TestCase;

#[CoversClass(FileCouldNotBeReadException::class)]
#[Small]
final class FileCouldNotBeReadExceptionTest extends TestCase
{
    public function testHasMessage(): void
    {
        $e = new FileCouldNotBeReadException('some message');

        $this->assertSame('some message', $e->getMessage());
    }
}
