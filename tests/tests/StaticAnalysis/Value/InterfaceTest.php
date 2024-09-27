<?php declare(strict_types=1);
/*
 * This file is part of phpunit/php-code-coverage.
 *
 * (c) Sebastian Bergmann <sebastian@phpunit.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace SebastianBergmann\CodeCoverage\StaticAnalysis;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Small;
use PHPUnit\Framework\TestCase;

#[CoversClass(Interface_::class)]
#[Small]
final class InterfaceTest extends TestCase
{
    public function testHasName(): void
    {
        $this->assertSame('Example', $this->interface()->name());
    }

    public function testHasNamespacedName(): void
    {
        $this->assertSame('example\Example', $this->interface()->namespacedName());
    }

    public function testHasNamespaced(): void
    {
        $this->assertSame('example', $this->interface()->namespace());
    }

    public function testHasStartLine(): void
    {
        $this->assertSame(1, $this->interface()->startLine());
    }

    public function testHasEndLine(): void
    {
        $this->assertSame(2, $this->interface()->endLine());
    }

    public function testMayHaveParentInterfaces(): void
    {
        $interfaces = ['example\AnInterface'];

        $this->assertSame($interfaces, $this->interface(parentInterfaces: $interfaces)->parentInterfaces());
    }

    private function interface(array $parentInterfaces = []): Interface_
    {
        return new Interface_(
            'Example',
            'example\Example',
            'example',
            1,
            2,
            $parentInterfaces,
        );
    }
}
