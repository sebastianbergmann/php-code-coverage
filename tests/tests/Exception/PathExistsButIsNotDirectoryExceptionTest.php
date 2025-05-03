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

#[CoversClass(PathExistsButIsNotDirectoryException::class)]
#[Small]
final class PathExistsButIsNotDirectoryExceptionTest extends TestCase
{
    public function testHasMessage(): void
    {
        $e = new PathExistsButIsNotDirectoryException('/not/a/directory');

        $this->assertSame('"/not/a/directory" exists but is not a directory', $e->getMessage());
    }
}
