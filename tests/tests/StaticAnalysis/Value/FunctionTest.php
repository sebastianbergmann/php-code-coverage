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

#[CoversClass(Function_::class)]
#[Small]
final class FunctionTest extends TestCase
{
    public function testHasName(): void
    {
        $this->assertSame('example', $this->function()->name());
    }

    public function testHasNamespacedName(): void
    {
        $this->assertSame('example\example', $this->function()->namespacedName());
    }

    public function testHasNamespace(): void
    {
        $this->assertSame('example', $this->function()->namespace());
    }

    public function testHasStartLine(): void
    {
        $this->assertSame(1, $this->function()->startLine());
    }

    public function testHasEndLine(): void
    {
        $this->assertSame(2, $this->function()->endLine());
    }

    public function testHasSignature(): void
    {
        $this->assertSame('the-signature', $this->function()->signature());
    }

    public function testHasCyclomaticComplexity(): void
    {
        $this->assertSame(3, $this->function()->cyclomaticComplexity());
    }

    private function function(): Function_
    {
        return new Function_(
            'example',
            'example\example',
            'example',
            1,
            2,
            'the-signature',
            3,
        );
    }
}
