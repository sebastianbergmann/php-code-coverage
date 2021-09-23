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

use function file_get_contents;
use PhpParser\NodeTraverser;
use PhpParser\NodeVisitor\NameResolver;
use PhpParser\NodeVisitor\ParentConnectingVisitor;
use PhpParser\ParserFactory;
use PHPUnit\Framework\TestCase;
use SebastianBergmann\CodeCoverage\TestFixture\ClassThatUsesAnonymousClass;
use SebastianBergmann\CodeCoverage\TestFixture\ClassWithNameThatIsPartOfItsNamespacesName\ClassWithNameThatIsPartOfItsNamespacesName;

/**
 * @covers \SebastianBergmann\CodeCoverage\StaticAnalysis\CodeUnitFindingVisitor
 */
final class CodeUnitFindingVisitorTest extends TestCase
{
    /**
     * @ticket https://github.com/sebastianbergmann/php-code-coverage/issues/786
     */
    public function testDoesNotFindAnonymousClass(): void
    {
        $codeUnitFindingVisitor = $this->findCodeUnits(__DIR__ . '/../../_files/ClassThatUsesAnonymousClass.php');

        $this->assertEmpty($codeUnitFindingVisitor->functions());
        $this->assertEmpty($codeUnitFindingVisitor->traits());

        $classes = $codeUnitFindingVisitor->classes();

        $this->assertCount(1, $classes);
        $this->assertArrayHasKey(ClassThatUsesAnonymousClass::class, $classes);

        $class = $classes[ClassThatUsesAnonymousClass::class];

        $this->assertSame('ClassThatUsesAnonymousClass', $class['name']);
        $this->assertSame(ClassThatUsesAnonymousClass::class, $class['namespacedName']);
        $this->assertSame('SebastianBergmann\CodeCoverage\TestFixture', $class['namespace']);
        $this->assertSame(4, $class['startLine']);
        $this->assertSame(17, $class['endLine']);

        $this->assertCount(1, $class['methods']);
        $this->assertArrayHasKey('method', $class['methods']);

        $method = $class['methods']['method'];

        $this->assertSame('method', $method['methodName']);
        $this->assertSame('method(): string', $method['signature']);
        $this->assertSame('public', $method['visibility']);
        $this->assertSame(6, $method['startLine']);
        $this->assertSame(16, $method['endLine']);
        $this->assertSame(1, $method['ccn']);
    }

    /**
     * @ticket https://github.com/sebastianbergmann/php-code-coverage/pull/797
     */
    public function testHandlesClassWithNameThatIsPartOfItsNamespacesName(): void
    {
        $codeUnitFindingVisitor = $this->findCodeUnits(__DIR__ . '/../../_files/ClassWithNameThatIsPartOfItsNamespacesName.php');

        $this->assertEmpty($codeUnitFindingVisitor->functions());
        $this->assertEmpty($codeUnitFindingVisitor->traits());

        $classes = $codeUnitFindingVisitor->classes();

        $this->assertCount(1, $classes);
        $this->assertArrayHasKey(ClassWithNameThatIsPartOfItsNamespacesName::class, $classes);

        $class = $classes[ClassWithNameThatIsPartOfItsNamespacesName::class];

        $this->assertSame('ClassWithNameThatIsPartOfItsNamespacesName', $class['name']);
        $this->assertSame(ClassWithNameThatIsPartOfItsNamespacesName::class, $class['namespacedName']);
        $this->assertSame('SebastianBergmann\CodeCoverage\TestFixture\ClassWithNameThatIsPartOfItsNamespacesName', $class['namespace']);
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
            $functions['SebastianBergmann\CodeCoverage\TestFixture\functionWithUnionTypes']['signature']
        );
    }

    /**
     * @requires PHP 8.1
     */
    public function testHandlesFunctionOrMethodWithIntersectionTypes(): void
    {
        $codeUnitFindingVisitor = $this->findCodeUnits(__DIR__ . '/../../_files/FunctionWithIntersectionTypes.php');

        $this->assertEmpty($codeUnitFindingVisitor->classes());
        $this->assertEmpty($codeUnitFindingVisitor->traits());

        $functions = $codeUnitFindingVisitor->functions();

        $this->assertCount(1, $functions);

        $this->assertSame(
            'functionWithIntersectionTypes(\SebastianBergmann\CodeCoverage\TestFixture\IntersectionPartOne&\SebastianBergmann\CodeCoverage\TestFixture\IntersectionPartTwo $x): \SebastianBergmann\CodeCoverage\TestFixture\IntersectionPartOne&\SebastianBergmann\CodeCoverage\TestFixture\IntersectionPartTwo',
            $functions['SebastianBergmann\CodeCoverage\TestFixture\functionWithIntersectionTypes']['signature']
        );
    }

    private function findCodeUnits(string $filename): CodeUnitFindingVisitor
    {
        $nodes = (new ParserFactory)->create(ParserFactory::PREFER_PHP7)->parse(
            file_get_contents($filename)
        );

        assert($nodes !== null);

        $traverser              = new NodeTraverser;
        $codeUnitFindingVisitor = new CodeUnitFindingVisitor;

        $traverser->addVisitor(new NameResolver);
        $traverser->addVisitor(new ParentConnectingVisitor);
        $traverser->addVisitor($codeUnitFindingVisitor);

        /* @noinspection UnusedFunctionResultInspection */
        $traverser->traverse($nodes);

        return $codeUnitFindingVisitor;
    }
}
