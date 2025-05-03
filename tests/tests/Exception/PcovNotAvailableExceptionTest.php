<?php declare(strict_types=1);
/*
 * This file is part of phpunit/php-code-coverage.
 *
 * (c) Sebastian Bergmann <sebastian@phpunit.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace SebastianBergmann\CodeCoverage\Driver;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Small;
use PHPUnit\Framework\TestCase;

#[CoversClass(PcovNotAvailableException::class)]
#[Small]
final class PcovNotAvailableExceptionTest extends TestCase
{
    public function testHasMessage(): void
    {
        $e = new PcovNotAvailableException;

        $this->assertSame('The PCOV extension is not available', $e->getMessage());
    }
}
