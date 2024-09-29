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

use function assert;
use function file_get_contents;
use PhpParser\NodeTraverser;
use PhpParser\NodeVisitor\NameResolver;
use PhpParser\NodeVisitor\ParentConnectingVisitor;
use PhpParser\ParserFactory;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Ticket;
use PHPUnit\Framework\TestCase;
use SebastianBergmann\CodeCoverage\TestFixture\ClassThatUsesAnonymousClass;
use SebastianBergmann\CodeCoverage\TestFixture\ClassWithNameThatIsPartOfItsNamespacesName\ClassWithNameThatIsPartOfItsNamespacesName;

#[CoversClass(CodeUnitFindingVisitor::class)]
final class CodeUnitFindingVisitorTest extends TestCase
{
    #[Ticket('https://github.com/sebastianbergmann/php-code-coverage/issues/786')]
    public function testDoesNotFindAnonymousClass(): void
    {
        $codeUnitFindingVisitor = $this->findCodeUnits(__DIR__ . '/../../_files/ClassThatUsesAnonymousClass.php');

        $this->assertEmpty($codeUnitFindingVisitor->functions());
        $this->assertEmpty($codeUnitFindingVisitor->traits());

        $classes = $codeUnitFindingVisitor->classes();

        $this->assertCount(1, $classes);
        $this->assertArrayHasKey(ClassThatUsesAnonymousClass::class, $classes);

        $class = $classes[ClassThatUsesAnonymousClass::class];

        $this->assertSame('ClassThatUsesAnonymousClass', $class->name());
        $this->assertSame(ClassThatUsesAnonymousClass::class, $class->namespacedName());
        $this->assertSame('SebastianBergmann\CodeCoverage\TestFixture', $class->namespace());
        $this->assertSame(4, $class->startLine());
        $this->assertSame(17, $class->endLine());

        $this->assertCount(1, $class->methods());
        $this->assertArrayHasKey('method', $class->methods());

        $method = $class->methods()['method'];

        $this->assertSame('method', $method->name());
        $this->assertSame('method(): string', $method->signature());
        $this->assertSame('public', $method->visibility());
        $this->assertSame(6, $method->startLine());
        $this->assertSame(16, $method->endLine());
        $this->assertSame(1, $method->cyclomaticComplexity());
    }

    #[Ticket('https://github.com/sebastianbergmann/php-code-coverage/pull/797')]
    public function testHandlesClassWithNameThatIsPartOfItsNamespacesName(): void
    {
        $codeUnitFindingVisitor = $this->findCodeUnits(__DIR__ . '/../../_files/ClassWithNameThatIsPartOfItsNamespacesName.php');

        $this->assertEmpty($codeUnitFindingVisitor->functions());
        $this->assertEmpty($codeUnitFindingVisitor->traits());

        $classes = $codeUnitFindingVisitor->classes();

        $this->assertCount(1, $classes);
        $this->assertArrayHasKey(ClassWithNameThatIsPartOfItsNamespacesName::class, $classes);

        $class = $classes[ClassWithNameThatIsPartOfItsNamespacesName::class];

        $this->assertSame('ClassWithNameThatIsPartOfItsNamespacesName', $class->name());
        $this->assertSame(ClassWithNameThatIsPartOfItsNamespacesName::class, $class->namespacedName());
        $this->assertSame('SebastianBergmann\CodeCoverage\TestFixture\ClassWithNameThatIsPartOfItsNamespacesName', $class->namespace());
    }

    public function testHandlesFunctionOrMethodWithUnionTypes(): void
    {
        $codeUnitFindingVisitor = $this->findCodeUnits(__DIR__ . '/../../_files/FunctionWithUnionTypes.php');

        $this->assertEmpty($codeUnitFindingVisitor->classes());
        $this->assertEmpty($codeUnitFindingVisitor->traits());

        $functions = $codeUnitFindingVisitor->functions();

        $this->assertCount(1, $functions);

        $this->assertSame(
            'functionWithUnionTypes(string|bool $x): string|bool',
            $functions['SebastianBergmann\CodeCoverage\TestFixture\functionWithUnionTypes']->signature(),
        );
    }

    public function testHandlesFunctionOrMethodWithIntersectionTypes(): void
    {
        $codeUnitFindingVisitor = $this->findCodeUnits(__DIR__ . '/../../_files/FunctionWithIntersectionTypes.php');

        $this->assertEmpty($codeUnitFindingVisitor->classes());
        $this->assertEmpty($codeUnitFindingVisitor->traits());

        $functions = $codeUnitFindingVisitor->functions();

        $this->assertCount(1, $functions);

        $this->assertSame(
            'functionWithIntersectionTypes(\SebastianBergmann\CodeCoverage\TestFixture\IntersectionPartOne&\SebastianBergmann\CodeCoverage\TestFixture\IntersectionPartTwo $x): \SebastianBergmann\CodeCoverage\TestFixture\IntersectionPartOne&\SebastianBergmann\CodeCoverage\TestFixture\IntersectionPartTwo',
            $functions['SebastianBergmann\CodeCoverage\TestFixture\functionWithIntersectionTypes']->signature(),
        );
    }

    public function testHandlesFunctionOrMethodWithDisjunctiveNormalFormTypes(): void
    {
        $codeUnitFindingVisitor = $this->findCodeUnits(__DIR__ . '/../../_files/FunctionWithDisjunctiveNormalFormTypes.php');

        $this->assertEmpty($codeUnitFindingVisitor->classes());
        $this->assertEmpty($codeUnitFindingVisitor->traits());

        $functions = $codeUnitFindingVisitor->functions();

        $this->assertCount(3, $functions);

        $this->assertSame(
            'f((\SebastianBergmann\CodeCoverage\TestFixture\A&\SebastianBergmann\CodeCoverage\TestFixture\B)|\SebastianBergmann\CodeCoverage\TestFixture\D $x): void',
            $functions['SebastianBergmann\CodeCoverage\TestFixture\f']->signature(),
        );

        $this->assertSame(
            'g(\SebastianBergmann\CodeCoverage\TestFixture\C|(\SebastianBergmann\CodeCoverage\TestFixture\X&\SebastianBergmann\CodeCoverage\TestFixture\D)|null $x): void',
            $functions['SebastianBergmann\CodeCoverage\TestFixture\g']->signature(),
        );

        $this->assertSame(
            'h((\SebastianBergmann\CodeCoverage\TestFixture\A&\SebastianBergmann\CodeCoverage\TestFixture\B&\SebastianBergmann\CodeCoverage\TestFixture\D)|int|null $x): void',
            $functions['SebastianBergmann\CodeCoverage\TestFixture\h']->signature(),
        );
    }

    public function testDetailsAboutExtendedClassesImplementedInterfacesAndUsedTraitsAreAvailable(): void
    {
        $codeUnitFindingVisitor = $this->findCodeUnits(__DIR__ . '/../../_files/source_with_interfaces_classes_traits_functions.php');

        $interfaces = $codeUnitFindingVisitor->interfaces();

        $this->assertCount(3, $interfaces);

        $a = 'SebastianBergmann\CodeCoverage\StaticAnalysis\A';
        $b = 'SebastianBergmann\CodeCoverage\StaticAnalysis\B';
        $c = 'SebastianBergmann\CodeCoverage\StaticAnalysis\C';

        $this->assertArrayHasKey($a, $interfaces);
        $this->assertArrayHasKey($b, $interfaces);
        $this->assertArrayHasKey($c, $interfaces);

        $this->assertSame('A', $interfaces[$a]->name());
        $this->assertSame($a, $interfaces[$a]->namespacedName());
        $this->assertSame('SebastianBergmann\CodeCoverage\StaticAnalysis', $interfaces[$a]->namespace());
        $this->assertSame(4, $interfaces[$a]->startLine());
        $this->assertSame(7, $interfaces[$a]->endLine());
        $this->assertSame([], $interfaces[$a]->parentInterfaces());

        $this->assertSame('B', $interfaces[$b]->name());
        $this->assertSame($b, $interfaces[$b]->namespacedName());
        $this->assertSame('SebastianBergmann\CodeCoverage\StaticAnalysis', $interfaces[$b]->namespace());
        $this->assertSame(9, $interfaces[$b]->startLine());
        $this->assertSame(12, $interfaces[$b]->endLine());
        $this->assertSame([], $interfaces[$b]->parentInterfaces());

        $this->assertSame('C', $interfaces[$c]->name());
        $this->assertSame($c, $interfaces[$c]->namespacedName());
        $this->assertSame('SebastianBergmann\CodeCoverage\StaticAnalysis', $interfaces[$c]->namespace());
        $this->assertSame(14, $interfaces[$c]->startLine());
        $this->assertSame(17, $interfaces[$c]->endLine());
        $this->assertSame([$a, $b], $interfaces[$c]->parentInterfaces());

        $traits = $codeUnitFindingVisitor->traits();

        $this->assertCount(1, $traits);

        $t = 'SebastianBergmann\CodeCoverage\StaticAnalysis\T';

        $this->assertArrayHasKey($t, $traits);

        $this->assertSame('T', $traits[$t]->name());
        $this->assertSame($t, $traits[$t]->namespacedName());
        $this->assertSame('SebastianBergmann\CodeCoverage\StaticAnalysis', $traits[$t]->namespace());
        $this->assertSame(19, $traits[$t]->startLine());
        $this->assertSame(24, $traits[$t]->endLine());

        $methods = $traits[$t]->methods();

        $this->assertCount(1, $methods);
        $this->assertArrayHasKey('four', $methods);

        $method = $methods['four'];

        $this->assertSame('four', $method->name());
        $this->assertSame(21, $method->startLine());
        $this->assertSame(23, $method->endLine());
        $this->assertSame('public', $method->visibility());
        $this->assertSame('four(): void', $method->signature());
        $this->assertSame(1, $method->cyclomaticComplexity());

        $classes = $codeUnitFindingVisitor->classes();

        $this->assertCount(2, $classes);

        $parentClass = 'SebastianBergmann\CodeCoverage\StaticAnalysis\ParentClass';
        $childClass  = 'SebastianBergmann\CodeCoverage\StaticAnalysis\ChildClass';

        $this->assertArrayHasKey($parentClass, $classes);
        $this->assertArrayHasKey($childClass, $classes);

        $this->assertSame('ParentClass', $classes[$parentClass]->name());
        $this->assertSame($parentClass, $classes[$parentClass]->namespacedName());
        $this->assertSame('SebastianBergmann\CodeCoverage\StaticAnalysis', $classes[$parentClass]->namespace());
        $this->assertSame(26, $classes[$parentClass]->startLine());
        $this->assertSame(31, $classes[$parentClass]->endLine());
        $this->assertNull($classes[$parentClass]->parentClass());
        $this->assertSame([$c], $classes[$parentClass]->interfaces());
        $this->assertSame([], $classes[$parentClass]->traits());

        $methods = $classes[$parentClass]->methods();

        $this->assertCount(1, $methods);
        $this->assertArrayHasKey('five', $methods);

        $method = $methods['five'];

        $this->assertSame('five', $method->name());
        $this->assertSame(28, $method->startLine());
        $this->assertSame(30, $method->endLine());
        $this->assertSame('public', $method->visibility());
        $this->assertSame('five(A $a, B $b): void', $method->signature());
        $this->assertSame(1, $method->cyclomaticComplexity());

        $this->assertSame('ChildClass', $classes[$childClass]->name());
        $this->assertSame($childClass, $classes[$childClass]->namespacedName());
        $this->assertSame('SebastianBergmann\CodeCoverage\StaticAnalysis', $classes[$childClass]->namespace());
        $this->assertSame(33, $classes[$childClass]->startLine());
        $this->assertSame(52, $classes[$childClass]->endLine());
        $this->assertSame($parentClass, $classes[$childClass]->parentClass());
        $this->assertSame([$a, $b], $classes[$childClass]->interfaces());
        $this->assertSame([$t], $classes[$childClass]->traits());

        $methods = $classes[$childClass]->methods();

        $this->assertCount(4, $methods);
        $this->assertArrayHasKey('one', $methods);
        $this->assertArrayHasKey('two', $methods);
        $this->assertArrayHasKey('three', $methods);
        $this->assertArrayHasKey('six', $methods);
    }

    private function findCodeUnits(string $filename): CodeUnitFindingVisitor
    {
        $nodes = (new ParserFactory)->createForHostVersion()->parse(
            file_get_contents($filename),
        );

        assert($nodes !== null);

        $traverser              = new NodeTraverser;
        $codeUnitFindingVisitor = new CodeUnitFindingVisitor($filename);

        $traverser->addVisitor(new NameResolver);
        $traverser->addVisitor(new ParentConnectingVisitor);
        $traverser->addVisitor($codeUnitFindingVisitor);

        /* @noinspection UnusedFunctionResultInspection */
        $traverser->traverse($nodes);

        return $codeUnitFindingVisitor;
    }
}
