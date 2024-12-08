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

#[CoversClass(Method::class)]
#[Small]
final class MethodTest extends TestCase
{
    public function testHasName(): void
    {
        $this->assertSame('example', $this->method()->name());
    }

    public function testHasStartLine(): void
    {
        $this->assertSame(1, $this->method()->startLine());
    }

    public function testHasEndLine(): void
    {
        $this->assertSame(2, $this->method()->endLine());
    }

    public function testHasSignature(): void
    {
        $this->assertSame('the-signature', $this->method()->signature());
    }

    public function testHasVisibility(): void
    {
        $this->assertSame(Visibility::Public, $this->method()->visibility());
    }

    public function testHasCyclomaticComplexity(): void
    {
        $this->assertSame(3, $this->method()->cyclomaticComplexity());
    }

    private function method(): Method
    {
        return new Method(
            'example',
            1,
            2,
            'the-signature',
            Visibility::Public,
            3,
        );
    }
}
