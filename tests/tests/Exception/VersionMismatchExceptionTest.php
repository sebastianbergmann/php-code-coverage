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

#[CoversClass(VersionMismatchException::class)]
#[Small]
final class VersionMismatchExceptionTest extends TestCase
{
    public function testHasMessage(): void
    {
        $e = new VersionMismatchException('1.0', '2.0');

        $this->assertSame(
            'Coverage data was written by version 1.0 and cannot be read by version 2.0',
            $e->getMessage(),
        );
    }
}
