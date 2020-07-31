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
        $nodes = (new ParserFactory)->create(ParserFactory::PREFER_PHP7)->parse(
            file_get_contents(__DIR__ . '/../../_files/ClassThatUsesAnonymousClass.php')
        );

        assert($nodes !== null);

        $traverser              = new NodeTraverser;
        $codeUnitFindingVisitor = new CodeUnitFindingVisitor;

        $traverser->addVisitor(new NameResolver);
        $traverser->addVisitor(new ParentConnectingVisitor);
        $traverser->addVisitor($codeUnitFindingVisitor);

        /* @noinspection UnusedFunctionResultInspection */
        $traverser->traverse($nodes);

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
}
