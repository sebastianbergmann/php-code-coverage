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
use SebastianBergmann\CodeCoverage\TestFixture\Target\TargetClass;
use SebastianBergmann\CodeCoverage\TestFixture\Target\TargetInterface;
use SebastianBergmann\CodeCoverage\TestFixture\Target\TraitOne;

#[CoversClass(Target::class)]
#[CoversClass(Class_::class)]
#[CoversClass(ClassesThatExtendClass::class)]
#[CoversClass(ClassesThatImplementInterface::class)]
#[CoversClass(Directory::class)]
#[CoversClass(File::class)]
#[CoversClass(Function_::class)]
#[CoversClass(Method::class)]
#[CoversClass(Namespace_::class)]
#[CoversClass(Trait_::class)]
#[Small]
final class TargetTest extends TestCase
{
    public function testCanBeClass(): void
    {
        $className = TargetClass::class;

        $target = Target::forClass($className);

        $this->assertTrue($target->isClass());
        $this->assertFalse($target->isClassesThatExtendClass());
        $this->assertFalse($target->isClassesThatImplementInterface());
        $this->assertFalse($target->isFunction());
        $this->assertFalse($target->isMethod());
        $this->assertFalse($target->isNamespace());
        $this->assertFalse($target->isTrait());

        $this->assertSame($className, $target->className());
        $this->assertSame('classes', $target->key());
        $this->assertSame($className, $target->target());
        $this->assertSame('Class ' . $className, $target->description());
    }

    public function testCanBeClassesThatExtendClass(): void
    {
        $className = TargetClass::class;

        $target = Target::forClassesThatExtendClass($className);

        $this->assertFalse($target->isClass());
        $this->assertTrue($target->isClassesThatExtendClass());
        $this->assertFalse($target->isClassesThatImplementInterface());
        $this->assertFalse($target->isFunction());
        $this->assertFalse($target->isMethod());
        $this->assertFalse($target->isNamespace());
        $this->assertFalse($target->isTrait());

        $this->assertSame($className, $target->className());
        $this->assertSame('classesThatExtendClass', $target->key());
        $this->assertSame($className, $target->target());
        $this->assertSame('Classes that extend class ' . $className, $target->description());
    }

    public function testCanBeClassesThatImplementInterface(): void
    {
        $interfaceName = TargetInterface::class;

        $target = Target::forClassesThatImplementInterface($interfaceName);

        $this->assertFalse($target->isClass());
        $this->assertFalse($target->isClassesThatExtendClass());
        $this->assertTrue($target->isClassesThatImplementInterface());
        $this->assertFalse($target->isFunction());
        $this->assertFalse($target->isMethod());
        $this->assertFalse($target->isNamespace());
        $this->assertFalse($target->isTrait());

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
        $this->assertFalse($target->isTrait());

        $this->assertSame($functionName, $target->functionName());
        $this->assertSame('functions', $target->key());
        $this->assertSame($functionName, $target->target());
        $this->assertSame('Function ' . $functionName, $target->description());
    }

    public function testCanBeMethod(): void
    {
        $className  = TargetClass::class;
        $methodName = 'method';

        $target = Target::forMethod($className, $methodName);

        $this->assertFalse($target->isClass());
        $this->assertFalse($target->isClassesThatExtendClass());
        $this->assertFalse($target->isClassesThatImplementInterface());
        $this->assertFalse($target->isFunction());
        $this->assertTrue($target->isMethod());
        $this->assertFalse($target->isNamespace());
        $this->assertFalse($target->isTrait());

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
        $this->assertFalse($target->isTrait());

        $this->assertSame($namespace, $target->namespace());
        $this->assertSame('namespaces', $target->key());
        $this->assertSame($namespace, $target->target());
        $this->assertSame('Namespace ' . $namespace, $target->description());
    }

    public function testCanBeTrait(): void
    {
        $traitName = TraitOne::class;

        $target = Target::forTrait($traitName);

        $this->assertTrue($target->isTrait());
        $this->assertFalse($target->isClass());
        $this->assertFalse($target->isClassesThatExtendClass());
        $this->assertFalse($target->isClassesThatImplementInterface());
        $this->assertFalse($target->isDirectory());
        $this->assertFalse($target->isFile());
        $this->assertFalse($target->isFunction());
        $this->assertFalse($target->isMethod());
        $this->assertFalse($target->isNamespace());

        $this->assertSame($traitName, $target->traitName());
        $this->assertSame('traits', $target->key());
        $this->assertSame($traitName, $target->target());
        $this->assertSame('Trait ' . $traitName, $target->description());
    }

    public function testCanBeFile(): void
    {
        $path = '/some/path/file.php';

        $target = Target::forFile($path);

        $this->assertTrue($target->isFile());
        $this->assertFalse($target->isClass());
        $this->assertFalse($target->isClassesThatExtendClass());
        $this->assertFalse($target->isClassesThatImplementInterface());
        $this->assertFalse($target->isDirectory());
        $this->assertFalse($target->isFunction());
        $this->assertFalse($target->isMethod());
        $this->assertFalse($target->isNamespace());
        $this->assertFalse($target->isTrait());

        $this->assertSame($path, $target->path());
        $this->assertSame('files', $target->key());
        $this->assertSame($path, $target->target());
        $this->assertSame('File ' . $path, $target->description());
    }

    public function testCanBeDirectory(): void
    {
        $path = '/some/path';

        $target = Target::forDirectory($path);

        $this->assertTrue($target->isDirectory());
        $this->assertFalse($target->isClass());
        $this->assertFalse($target->isClassesThatExtendClass());
        $this->assertFalse($target->isClassesThatImplementInterface());
        $this->assertFalse($target->isFile());
        $this->assertFalse($target->isFunction());
        $this->assertFalse($target->isMethod());
        $this->assertFalse($target->isNamespace());
        $this->assertFalse($target->isTrait());

        $this->assertSame($path, $target->path());
        $this->assertSame('directories', $target->key());
        $this->assertSame($path, $target->target());
        $this->assertSame('Directory ' . $path, $target->description());
    }
}
