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

#[CoversClass(Trait_::class)]
#[Small]
final class TraitTest extends TestCase
{
    public function testHasName(): void
    {
        $this->assertSame('Example', $this->trait()->name());
    }

    public function testHasNamespacedName(): void
    {
        $this->assertSame('example\Example', $this->trait()->namespacedName());
    }

    public function testHasNamespace(): void
    {
        $this->assertSame('example', $this->trait()->namespace());
    }

    public function testHasFile(): void
    {
        $this->assertSame('file.php', $this->trait()->file());
    }

    public function testHasStartLine(): void
    {
        $this->assertSame(1, $this->trait()->startLine());
    }

    public function testHasEndLine(): void
    {
        $this->assertSame(2, $this->trait()->endLine());
    }

    public function testMayHaveTraits(): void
    {
        $traits = ['trait'];

        $this->assertSame($traits, $this->trait(traits: $traits)->traits());
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

        $this->assertSame($methods, $this->trait(methods: $methods)->methods());
    }

    /**
     * @param list<non-empty-string>          $traits
     * @param array<non-empty-string, Method> $methods
     */
    private function trait(array $traits = [], array $methods = []): Trait_
    {
        return new Trait_(
            'Example',
            'example\Example',
            'example',
            'file.php',
            1,
            2,
            $traits,
            $methods,
        );
    }
}
