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

#[CoversClass(VersionMismatchException::class)]
#[Small]
final class VersionMismatchExceptionTest extends TestCase
{
    public function testHasMessage(): void
    {
        $e = new VersionMismatchException(1, 2);

        $this->assertSame(
            'Coverage data was written using serialization format 1 and cannot be read by code that supports serialization format 2',
            $e->getMessage(),
        );
    }
}
