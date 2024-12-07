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

#[CoversClass(Target::class)]
#[CoversClass(Class_::class)]
#[CoversClass(ClassesThatExtendClass::class)]
#[CoversClass(ClassesThatImplementInterface::class)]
#[CoversClass(Function_::class)]
#[CoversClass(Method::class)]
#[CoversClass(Namespace_::class)]
#[Small]
final class TargetTest extends TestCase
{
    public function testCanBeClass(): void
    {
        $className = 'className';

        $target = Target::forClass($className);

        $this->assertTrue($target->isClass());
        $this->assertFalse($target->isClassesThatExtendClass());
        $this->assertFalse($target->isClassesThatImplementInterface());
        $this->assertFalse($target->isFunction());
        $this->assertFalse($target->isMethod());
        $this->assertFalse($target->isNamespace());

        $this->assertSame($className, $target->className());
        $this->assertSame('classes', $target->key());
        $this->assertSame($className, $target->target());
        $this->assertSame('Class ' . $className, $target->description());
    }

    public function testCanBeClassesThatExtendClass(): void
    {
        $className = 'className';

        $target = Target::forClassesThatExtendClass($className);

        $this->assertFalse($target->isClass());
        $this->assertTrue($target->isClassesThatExtendClass());
        $this->assertFalse($target->isClassesThatImplementInterface());
        $this->assertFalse($target->isFunction());
        $this->assertFalse($target->isMethod());
        $this->assertFalse($target->isNamespace());

        $this->assertSame($className, $target->className());
        $this->assertSame('classesThatExtendClass', $target->key());
        $this->assertSame($className, $target->target());
        $this->assertSame('Classes that extend class ' . $className, $target->description());
    }

    public function testCanBeClassesThatImplementInterface(): void
    {
        $interfaceName = 'interfaceName';

        $target = Target::forClassesThatImplementInterface($interfaceName);

        $this->assertFalse($target->isClass());
        $this->assertFalse($target->isClassesThatExtendClass());
        $this->assertTrue($target->isClassesThatImplementInterface());
        $this->assertFalse($target->isFunction());
        $this->assertFalse($target->isMethod());
        $this->assertFalse($target->isNamespace());

        $this->assertSame($interfaceName, $target->interfaceName());
        $this->assertSame('classesThatImplementInterface', $target->key());
        $this->assertSame($interfaceName, $target->target());
        $this->assertSame('Classes that implement interface ' . $interfaceName, $target->description());
    }

    public function testCanBeFunction(): void
    {
        $functionName = 'function';

        $target = Target::forFunction($functionName);

        $this->assertFalse($target->isClass());
        $this->assertFalse($target->isClassesThatExtendClass());
        $this->assertFalse($target->isClassesThatImplementInterface());
        $this->assertTrue($target->isFunction());
        $this->assertFalse($target->isMethod());
        $this->assertFalse($target->isNamespace());

        $this->assertSame($functionName, $target->functionName());
        $this->assertSame('functions', $target->key());
        $this->assertSame($functionName, $target->target());
        $this->assertSame('Function ' . $functionName, $target->description());
    }

    public function testCanBeMethod(): void
    {
        $className  = 'className';
        $methodName = 'methodName';

        $target = Target::forMethod($className, $methodName);

        $this->assertFalse($target->isClass());
        $this->assertFalse($target->isClassesThatExtendClass());
        $this->assertFalse($target->isClassesThatImplementInterface());
        $this->assertFalse($target->isFunction());
        $this->assertTrue($target->isMethod());
        $this->assertFalse($target->isNamespace());

        $this->assertSame($className, $target->className());
        $this->assertSame($methodName, $target->methodName());
        $this->assertSame('methods', $target->key());
        $this->assertSame($className . '::' . $methodName, $target->target());
        $this->assertSame('Method ' . $className . '::' . $methodName, $target->description());
    }

    public function testCanBeNamespace(): void
    {
        $namespace = 'namespace';

        $target = Target::forNamespace($namespace);

        $this->assertFalse($target->isClass());
        $this->assertFalse($target->isClassesThatExtendClass());
        $this->assertFalse($target->isClassesThatImplementInterface());
        $this->assertFalse($target->isFunction());
        $this->assertFalse($target->isMethod());
        $this->assertTrue($target->isNamespace());

        $this->assertSame($namespace, $target->namespace());
        $this->assertSame('namespaces', $target->key());
        $this->assertSame($namespace, $target->target());
        $this->assertSame('Namespace ' . $namespace, $target->description());
    }
}
