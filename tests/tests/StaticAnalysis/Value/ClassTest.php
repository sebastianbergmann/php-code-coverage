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

#[CoversClass(Class_::class)]
#[Small]
final class ClassTest extends TestCase
{
    public function testHasName(): void
    {
        $this->assertSame('Example', $this->class()->name());
    }

    public function testHasNamespacedName(): void
    {
        $this->assertSame('example\Example', $this->class()->namespacedName());
    }

    public function testHasNamespaced(): void
    {
        $this->assertSame('example', $this->class()->namespace());
    }

    public function testHasFile(): void
    {
        $this->assertSame('file.php', $this->class()->file());
    }

    public function testHasStartLine(): void
    {
        $this->assertSame(1, $this->class()->startLine());
    }

    public function testHasEndLine(): void
    {
        $this->assertSame(2, $this->class()->endLine());
    }

    public function testMayHaveParentClass(): void
    {
        $parentClass = 'example\ParentClass';

        $class = $this->class(parentClass: $parentClass);

        $this->assertTrue($class->hasParent());
        $this->assertSame($parentClass, $class->parentClass());
    }

    public function testMayNotHaveParentClass(): void
    {
        $this->assertFalse($this->class()->hasParent());
        $this->assertNull($this->class()->parentClass());
    }

    public function testMayImplementInterfaces(): void
    {
        $interfaces = ['example\AnInterface'];

        $this->assertSame($interfaces, $this->class(interfaces: $interfaces)->interfaces());
    }

    public function testMayUseTraits(): void
    {
        $traits = ['example\ATrait'];

        $this->assertSame($traits, $this->class(traits: $traits)->traits());
    }

    public function testMayHaveMethods(): void
    {
        $methods = [
            'method' => new Method(
                'method',
                0,
                0,
                'method(): void',
                Visibility::Public,
                1,
            ),
        ];

        $this->assertSame($methods, $this->class(methods: $methods)->methods());
    }

    /**
     * @param list<non-empty-string>          $interfaces
     * @param list<non-empty-string>          $traits
     * @param array<non-empty-string, Method> $methods
     */
    private function class(?string $parentClass = null, array $interfaces = [], array $traits = [], array $methods = []): Class_
    {
        return new Class_(
            'Example',
            'example\Example',
            'example',
            'file.php',
            1,
            2,
            $parentClass,
            $interfaces,
            $traits,
            $methods,
        );
    }
}
