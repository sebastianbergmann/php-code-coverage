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

#[CoversClass(JsonException::class)]
#[Small]
final class JsonExceptionTest extends TestCase
{
    public function testIsPartOfTheExceptionHierarchyOfThisPackage(): void
    {
        $e = new JsonException('message');

        $this->assertInstanceOf(Exception::class, $e);
        $this->assertSame('message', $e->getMessage());
    }
}
