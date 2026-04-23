<?php declare(strict_types=1);
/*
 * This file is part of phpunit/php-code-coverage.
 *
 * (c) Sebastian Bergmann <sebastian@phpunit.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace SebastianBergmann\CodeCoverage\Report\Html\ClassView\Node;

use function iterator_to_array;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Small;
use PHPUnit\Framework\TestCase;
use SebastianBergmann\CodeCoverage\Data\ProcessedClassType;
use SebastianBergmann\CodeCoverage\Data\ProcessedMethodType;
use SebastianBergmann\CodeCoverage\Node\Directory;
use SebastianBergmann\CodeCoverage\Node\File;
use SebastianBergmann\CodeCoverage\StaticAnalysis\LinesOfCode;

#[CoversClass(NamespaceNode::class)]
#[Small]
final class NamespaceNodeTest extends TestCase
{
    public function testNameAndNamespace(): void
    {
        $node = new NamespaceNode('Foo', 'App\\Foo');

        $this->assertSame('Foo', $node->name());
        $this->assertSame('App\\Foo', $node->namespace());
    }

    public function testParentIsNullByDefault(): void
    {
        $node = new NamespaceNode('Root', '');

        $this->assertNull($node->parent());
    }

    public function testParentCanBeSet(): void
    {
        $parent = new NamespaceNode('Root', '');
        $child  = new NamespaceNode('Child', 'Child', $parent);

        $this->assertSame($parent, $child->parent());
    }

    public function testPromoteToRoot(): void
    {
        $parent = new NamespaceNode('Root', '');
        $child  = new NamespaceNode('Child', 'Child', $parent);

        $child->promoteToRoot();

        $this->assertNull($child->parent());
    }

    public function testIdForRootNode(): void
    {
        $root = new NamespaceNode('Root', '');
        $root->promoteToRoot();

        $this->assertSame('index', $root->id());
    }

    public function testIdForDirectChild(): void
    {
        $root = new NamespaceNode('Root', '');
        $root->promoteToRoot();
        $child = new NamespaceNode('App', 'App', $root);

        $this->assertSame('App', $child->id());
    }

    public function testIdForNestedChild(): void
    {
        $root = new NamespaceNode('Root', '');
        $root->promoteToRoot();
        $child  = new NamespaceNode('App', 'App', $root);
        $nested = new NamespaceNode('Models', 'App\\Models', $child);

        $this->assertSame('App/Models', $nested->id());
    }

    public function testPathAsArrayForRoot(): void
    {
        $root = new NamespaceNode('Root', '');
        $root->promoteToRoot();

        $path = $root->pathAsArray();

        $this->assertCount(1, $path);
        $this->assertSame($root, $path[0]);
    }

    public function testPathAsArrayForNestedNode(): void
    {
        $root = new NamespaceNode('Root', '');
        $root->promoteToRoot();
        $child = new NamespaceNode('App', 'App', $root);

        $path = $child->pathAsArray();

        $this->assertCount(2, $path);
        $this->assertSame($root, $path[0]);
        $this->assertSame($child, $path[1]);
    }

    public function testAddNamespace(): void
    {
        $root  = new NamespaceNode('Root', '');
        $child = new NamespaceNode('App', 'App', $root);

        $root->addNamespace($child);

        $this->assertCount(1, $root->childNamespaces());
        $this->assertSame($child, $root->childNamespaces()[0]);
    }

    public function testAddClass(): void
    {
        $root      = new NamespaceNode('Root', '');
        $classNode = $this->createClassNode($root);

        $root->addClass($classNode);

        $this->assertCount(1, $root->classes());
        $this->assertSame($classNode, $root->classes()[0]);
    }

    public function testCountersWithClasses(): void
    {
        $root      = new NamespaceNode('Root', '');
        $classNode = $this->createClassNode($root, 10, 5, 4, 2, 2, 1);

        $root->addClass($classNode);

        $this->assertSame(10, $root->numberOfExecutableLines());
        $this->assertSame(5, $root->numberOfExecutedLines());
        $this->assertSame(4, $root->numberOfExecutableBranches());
        $this->assertSame(2, $root->numberOfExecutedBranches());
        $this->assertSame(2, $root->numberOfExecutablePaths());
        $this->assertSame(1, $root->numberOfExecutedPaths());
    }

    public function testCountersAggregateChildNamespaces(): void
    {
        $root  = new NamespaceNode('Root', '');
        $child = new NamespaceNode('Child', 'Child', $root);

        $classNode = $this->createClassNode($child, 10, 5, 4, 2, 2, 1);
        $child->addClass($classNode);
        $root->addNamespace($child);

        $this->assertSame(10, $root->numberOfExecutableLines());
        $this->assertSame(5, $root->numberOfExecutedLines());
        $this->assertSame(4, $root->numberOfExecutableBranches());
        $this->assertSame(2, $root->numberOfExecutedBranches());
        $this->assertSame(2, $root->numberOfExecutablePaths());
        $this->assertSame(1, $root->numberOfExecutedPaths());
    }

    public function testNumberOfClassesExcludesClassesWithNoMethods(): void
    {
        $root = new NamespaceNode('Root', '');
        $root->addClass($this->createClassNode($root, 0, 0, 0, 0, 0, 0, []));

        $this->assertSame(0, $root->numberOfClasses());
    }

    public function testNumberOfClassesCountsClassesWithMethods(): void
    {
        $root = new NamespaceNode('Root', '');
        $root->addClass($this->createClassNode($root));

        $this->assertSame(1, $root->numberOfClasses());
    }

    public function testNumberOfTestedClasses(): void
    {
        $root = new NamespaceNode('Root', '');

        $testedMethod = new ProcessedMethodType('testedMethod', 'public', 'public function testedMethod(): void', 1, 5, 3, 3, 0, 0, 0, 0, 1, 100, 1, '');
        $root->addClass($this->createClassNode($root, 3, 3, 0, 0, 0, 0, ['testedMethod' => $testedMethod]));

        $this->assertSame(1, $root->numberOfTestedClasses());
    }

    public function testNumberOfTestedClassesExcludesPartialCoverage(): void
    {
        $root = new NamespaceNode('Root', '');
        $root->addClass($this->createClassNode($root));

        $this->assertSame(0, $root->numberOfTestedClasses());
    }

    public function testNumberOfClassesAggregatesChildNamespaces(): void
    {
        $root  = new NamespaceNode('Root', '');
        $child = new NamespaceNode('Child', 'Child', $root);

        $child->addClass($this->createClassNode($child));
        $root->addNamespace($child);

        $this->assertSame(1, $root->numberOfClasses());
    }

    public function testNumberOfTestedClassesAggregatesChildNamespaces(): void
    {
        $root  = new NamespaceNode('Root', '');
        $child = new NamespaceNode('Child', 'Child', $root);

        $testedMethod = new ProcessedMethodType('m', 'public', 'public function m(): void', 1, 5, 3, 3, 0, 0, 0, 0, 1, 100, 1, '');
        $child->addClass($this->createClassNode($child, 3, 3, 0, 0, 0, 0, ['m' => $testedMethod]));
        $root->addNamespace($child);

        $this->assertSame(1, $root->numberOfTestedClasses());
    }

    public function testNumberOfMethodsAggregatesChildNamespaces(): void
    {
        $root  = new NamespaceNode('Root', '');
        $child = new NamespaceNode('Child', 'Child', $root);

        $child->addClass($this->createClassNode($child));
        $root->addNamespace($child);

        $this->assertSame(1, $root->numberOfMethods());
    }

    public function testNumberOfTestedMethodsAggregatesChildNamespaces(): void
    {
        $root  = new NamespaceNode('Root', '');
        $child = new NamespaceNode('Child', 'Child', $root);

        $testedMethod = new ProcessedMethodType('m', 'public', 'public function m(): void', 1, 5, 3, 3, 0, 0, 0, 0, 1, 100, 1, '');
        $child->addClass($this->createClassNode($child, 3, 3, 0, 0, 0, 0, ['m' => $testedMethod]));
        $root->addNamespace($child);

        $this->assertSame(1, $root->numberOfTestedMethods());
    }

    public function testPercentages(): void
    {
        $root = new NamespaceNode('Root', '');
        $root->addClass($this->createClassNode($root, 10, 5, 4, 2, 2, 1));

        $this->assertSame(50.0, $root->percentageOfExecutedLines()->asFloat());
        $this->assertSame(50.0, $root->percentageOfExecutedBranches()->asFloat());
        $this->assertSame(50.0, $root->percentageOfExecutedPaths()->asFloat());
    }

    public function testPercentageOfTestedMethods(): void
    {
        $root           = new NamespaceNode('Root', '');
        $testedMethod   = new ProcessedMethodType('m', 'public', 'public function m(): void', 1, 5, 3, 3, 0, 0, 0, 0, 1, 100, 1, '');
        $untestedMethod = new ProcessedMethodType('n', 'public', 'public function n(): void', 6, 10, 3, 0, 0, 0, 0, 0, 1, 0, 1, '');

        $root->addClass($this->createClassNode($root, 6, 3, 0, 0, 0, 0, ['m' => $testedMethod, 'n' => $untestedMethod]));

        $this->assertSame(50.0, $root->percentageOfTestedMethods()->asFloat());
    }

    public function testPercentageOfTestedClasses(): void
    {
        $root = new NamespaceNode('Root', '');

        $testedMethod = new ProcessedMethodType('m', 'public', 'public function m(): void', 1, 5, 3, 3, 0, 0, 0, 0, 1, 100, 1, '');
        $root->addClass($this->createClassNode($root, 3, 3, 0, 0, 0, 0, ['m' => $testedMethod]));
        $root->addClass($this->createClassNode($root, 3, 0, 0, 0, 0, 0));

        $this->assertSame(50.0, $root->percentageOfTestedClasses()->asFloat());
    }

    public function testAllClassTypes(): void
    {
        $root  = new NamespaceNode('Root', '');
        $child = new NamespaceNode('Child', 'Child', $root);

        $root->addClass($this->createClassNode($root));
        $child->addClass($this->createClassNode($child, 10, 5, 0, 0, 0, 0, null, 'App\\Other'));
        $root->addNamespace($child);

        $result = $root->allClassTypes();

        $this->assertCount(2, $result);
        $this->assertArrayHasKey('App\\MyClass', $result);
        $this->assertArrayHasKey('App\\Other', $result);
    }

    public function testIterate(): void
    {
        $root  = new NamespaceNode('Root', '');
        $child = new NamespaceNode('Child', 'Child', $root);

        $rootClass  = $this->createClassNode($root);
        $childClass = $this->createClassNode($child, 10, 5, 0, 0, 0, 0, null, 'App\\Other');

        $root->addClass($rootClass);
        $child->addClass($childClass);
        $root->addNamespace($child);

        $nodes = iterator_to_array($root->iterate(), false);

        $this->assertCount(3, $nodes);
        $this->assertSame($child, $nodes[0]);
        $this->assertSame($childClass, $nodes[1]);
        $this->assertSame($rootClass, $nodes[2]);
    }

    public function testCountersResetWhenAddingClass(): void
    {
        $root = new NamespaceNode('Root', '');
        $root->addClass($this->createClassNode($root, 10, 5, 0, 0, 0, 0));

        $this->assertSame(10, $root->numberOfExecutableLines());

        $root->addClass($this->createClassNode($root, 20, 10, 0, 0, 0, 0, null, 'App\\Other'));

        $this->assertSame(30, $root->numberOfExecutableLines());
    }

    public function testCountersResetWhenAddingNamespace(): void
    {
        $root  = new NamespaceNode('Root', '');
        $child = new NamespaceNode('Child', 'Child', $root);
        $child->addClass($this->createClassNode($child, 10, 5, 0, 0, 0, 0));

        $this->assertSame(0, $root->numberOfExecutableLines());

        $root->addNamespace($child);

        $this->assertSame(10, $root->numberOfExecutableLines());
    }

    /**
     * @param null|array<string, ProcessedMethodType> $methods
     */
    private function createClassNode(NamespaceNode $parent, int $executableLines = 10, int $executedLines = 5, int $executableBranches = 0, int $executedBranches = 0, int $executablePaths = 0, int $executedPaths = 0, ?array $methods = null, string $className = 'App\\MyClass'): ClassNode
    {
        $root = new Directory('root');

        if ($methods === null) {
            $methods = [
                'doSomething' => new ProcessedMethodType('doSomething', 'public', 'public function doSomething(): void', 1, 5, $executableLines, $executedLines, $executableBranches, $executedBranches, $executablePaths, $executedPaths, 1, $executableLines > 0 ? ($executedLines / $executableLines) * 100 : 0, 1, ''),
            ];
        }

        $processedClass = new ProcessedClassType($className, 'App', $methods, 1, $executableLines, $executedLines, $executableBranches, $executedBranches, $executablePaths, $executedPaths, 1, $executableLines > 0 ? ($executedLines / $executableLines) * 100 : 0, 1, '');

        $fileNode = new File('test.php', $root, 'abc123', [], [], [], [], [], [], new LinesOfCode(0, 0, 0));

        return new ClassNode($className, 'App', '/path/to/test.php', 1, 20, $processedClass, $fileNode, [], [], $parent);
    }
}
