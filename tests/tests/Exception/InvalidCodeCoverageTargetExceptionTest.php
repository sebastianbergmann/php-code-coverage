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
use PHPUnit\Framework\Attributes\Small;
use PHPUnit\Framework\TestCase;

#[CoversClass(InvalidCodeCoverageTargetException::class)]
#[Small]
final class InvalidCodeCoverageTargetExceptionTest extends TestCase
{
    public function testHasMessage(): void
    {
        /** @phpstan-ignore argument.type */
        $e = new InvalidCodeCoverageTargetException(Target::forClass('DoesNotExist'));

        $this->assertSame('Class DoesNotExist is not a valid target for code coverage', $e->getMessage());
    }
}
