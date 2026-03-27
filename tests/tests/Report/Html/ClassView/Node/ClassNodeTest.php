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

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Small;
use PHPUnit\Framework\TestCase;
use SebastianBergmann\CodeCoverage\Data\ProcessedClassType;
use SebastianBergmann\CodeCoverage\Data\ProcessedMethodType;
use SebastianBergmann\CodeCoverage\Data\ProcessedTraitType;
use SebastianBergmann\CodeCoverage\Node\Directory;
use SebastianBergmann\CodeCoverage\Node\File;
use SebastianBergmann\CodeCoverage\StaticAnalysis\LinesOfCode;

#[CoversClass(ClassNode::class)]
#[Small]
final class ClassNodeTest extends TestCase
{
    public function testClassName(): void
    {
        $node = $this->createClassNode();

        $this->assertSame('App\\Models\\User', $node->className());
    }

    public function testShortName(): void
    {
        $node = $this->createClassNode();

        $this->assertSame('User', $node->shortName());
    }

    public function testShortNameForNonNamespacedClass(): void
    {
        $parent = new NamespaceNode('Root', '');
        $node   = $this->createClassNodeWithName('MyClass', '', $parent);

        $this->assertSame('MyClass', $node->shortName());
    }

    public function testNamespace(): void
    {
        $node = $this->createClassNode();

        $this->assertSame('App\\Models', $node->namespace());
    }

    public function testFilePath(): void
    {
        $node = $this->createClassNode();

        $this->assertSame('/path/to/User.php', $node->filePath());
    }

    public function testStartAndEndLine(): void
    {
        $node = $this->createClassNode();

        $this->assertSame(10, $node->startLine());
        $this->assertSame(50, $node->endLine());
    }

    public function testClass(): void
    {
        $node = $this->createClassNode();

        $this->assertInstanceOf(ProcessedClassType::class, $node->class_());
    }

    public function testFileNode(): void
    {
        $node = $this->createClassNode();

        $this->assertInstanceOf(File::class, $node->fileNode());
    }

    public function testTraitSections(): void
    {
        $node = $this->createClassNodeWithTrait();

        $this->assertCount(1, $node->traitSections());
        $this->assertSame('App\\MyTrait', $node->traitSections()[0]->traitName);
    }

    public function testParentSections(): void
    {
        $node = $this->createClassNodeWithParent();

        $this->assertCount(1, $node->parentSections());
        $this->assertSame('App\\BaseClass', $node->parentSections()[0]->className);
    }

    public function testParent(): void
    {
        $node = $this->createClassNode();

        $this->assertInstanceOf(NamespaceNode::class, $node->parent());
    }

    public function testAllMethodsIncludesOwnMethods(): void
    {
        $node    = $this->createClassNode();
        $methods = $node->allMethods();

        $this->assertArrayHasKey('doSomething', $methods);
    }

    public function testAllMethodsIncludesTraitMethods(): void
    {
        $node    = $this->createClassNodeWithTrait();
        $methods = $node->allMethods();

        $this->assertArrayHasKey('[App\\MyTrait] traitMethod', $methods);
    }

    public function testAllMethodsIncludesParentMethods(): void
    {
        $node    = $this->createClassNodeWithParent();
        $methods = $node->allMethods();

        $this->assertArrayHasKey('[App\\BaseClass] parentMethod', $methods);
    }

    public function testNumberOfExecutableLinesIncludesTraitsAndParents(): void
    {
        $node = $this->createClassNodeWithTraitAndParent();

        // Own: 10, trait: 5, parent method: 3
        $this->assertSame(18, $node->numberOfExecutableLines());
    }

    public function testNumberOfExecutedLinesIncludesTraitsAndParents(): void
    {
        $node = $this->createClassNodeWithTraitAndParent();

        // Own: 7, trait: 3, parent method: 2
        $this->assertSame(12, $node->numberOfExecutedLines());
    }

    public function testNumberOfExecutableBranchesIncludesTraitsAndParents(): void
    {
        $node = $this->createClassNodeWithTraitAndParent();

        // Own: 4, trait: 2, parent method: 1
        $this->assertSame(7, $node->numberOfExecutableBranches());
    }

    public function testNumberOfExecutedBranchesIncludesTraitsAndParents(): void
    {
        $node = $this->createClassNodeWithTraitAndParent();

        // Own: 2, trait: 1, parent method: 0
        $this->assertSame(3, $node->numberOfExecutedBranches());
    }

    public function testNumberOfExecutablePathsIncludesTraitsAndParents(): void
    {
        $node = $this->createClassNodeWithTraitAndParent();

        // Own: 6, trait: 3, parent method: 2
        $this->assertSame(11, $node->numberOfExecutablePaths());
    }

    public function testNumberOfExecutedPathsIncludesTraitsAndParents(): void
    {
        $node = $this->createClassNodeWithTraitAndParent();

        // Own: 3, trait: 1, parent method: 1
        $this->assertSame(5, $node->numberOfExecutedPaths());
    }

    public function testNumberOfMethodsCountsOnlyMethodsWithExecutableLines(): void
    {
        $parent = new NamespaceNode('Root', '');
        $root   = new Directory('root');

        $methodWithLines    = new ProcessedMethodType('a', 'public', 'public function a(): void', 1, 5, 3, 3, 0, 0, 0, 0, 1, 100, 1, '');
        $methodWithoutLines = new ProcessedMethodType('b', 'public', 'public function b(): void', 6, 10, 0, 0, 0, 0, 0, 0, 1, 0, 1, '');

        $processedClass = new ProcessedClassType('MyClass', '', ['a' => $methodWithLines, 'b' => $methodWithoutLines], 1, 3, 3, 0, 0, 0, 0, 1, 100, 1, '');
        $fileNode       = new File('test.php', $root, 'abc123', [], [], [], [], [], [], new LinesOfCode(0, 0, 0));

        $node = new ClassNode('MyClass', '', '/test.php', 1, 10, $processedClass, $fileNode, [], [], $parent);

        $this->assertSame(1, $node->numberOfMethods());
    }

    public function testNumberOfTestedMethodsCountsFullyCoveredOnly(): void
    {
        $parent = new NamespaceNode('Root', '');
        $root   = new Directory('root');

        $testedMethod   = new ProcessedMethodType('a', 'public', 'public function a(): void', 1, 5, 3, 3, 0, 0, 0, 0, 1, 100, 1, '');
        $untestedMethod = new ProcessedMethodType('b', 'public', 'public function b(): void', 6, 10, 3, 1, 0, 0, 0, 0, 1, 33, 2, '');

        $processedClass = new ProcessedClassType('MyClass', '', ['a' => $testedMethod, 'b' => $untestedMethod], 1, 6, 4, 0, 0, 0, 0, 2, 66, 2, '');
        $fileNode       = new File('test.php', $root, 'abc123', [], [], [], [], [], [], new LinesOfCode(0, 0, 0));

        $node = new ClassNode('MyClass', '', '/test.php', 1, 10, $processedClass, $fileNode, [], [], $parent);

        $this->assertSame(1, $node->numberOfTestedMethods());
    }

    public function testNumberOfMethodsIsCached(): void
    {
        $node = $this->createClassNode();

        $first  = $node->numberOfMethods();
        $second = $node->numberOfMethods();

        $this->assertSame($first, $second);
    }

    public function testNumberOfTestedMethodsIsCached(): void
    {
        $node = $this->createClassNode();

        $first  = $node->numberOfTestedMethods();
        $second = $node->numberOfTestedMethods();

        $this->assertSame($first, $second);
    }

    public function testPercentageOfExecutedLines(): void
    {
        $node = $this->createClassNode();

        $this->assertSame(70.0, $node->percentageOfExecutedLines()->asFloat());
    }

    public function testPercentageOfExecutedBranches(): void
    {
        $node = $this->createClassNode();

        $this->assertSame(50.0, $node->percentageOfExecutedBranches()->asFloat());
    }

    public function testPercentageOfExecutedPaths(): void
    {
        $node = $this->createClassNode();

        $this->assertSame(50.0, $node->percentageOfExecutedPaths()->asFloat());
    }

    public function testPercentageOfTestedMethods(): void
    {
        $node = $this->createClassNode();

        // 0 out of 1 methods fully tested
        $this->assertSame(0.0, $node->percentageOfTestedMethods()->asFloat());
    }

    public function testPercentageOfTestedClassesWhenFullyTested(): void
    {
        $parent = new NamespaceNode('Root', '');
        $root   = new Directory('root');

        $method         = new ProcessedMethodType('m', 'public', 'public function m(): void', 1, 5, 3, 3, 0, 0, 0, 0, 1, 100, 1, '');
        $processedClass = new ProcessedClassType('MyClass', '', ['m' => $method], 1, 3, 3, 0, 0, 0, 0, 1, 100, 1, '');
        $fileNode       = new File('test.php', $root, 'abc123', [], [], [], [], [], [], new LinesOfCode(0, 0, 0));

        $node = new ClassNode('MyClass', '', '/test.php', 1, 5, $processedClass, $fileNode, [], [], $parent);

        $this->assertSame(100.0, $node->percentageOfTestedClasses()->asFloat());
    }

    public function testPercentageOfTestedClassesWhenNotFullyTested(): void
    {
        $node = $this->createClassNode();

        $this->assertSame(0.0, $node->percentageOfTestedClasses()->asFloat());
    }

    public function testPercentageOfTestedClassesWithNoMethods(): void
    {
        $parent = new NamespaceNode('Root', '');
        $root   = new Directory('root');

        $processedClass = new ProcessedClassType('MyClass', '', [], 1, 0, 0, 0, 0, 0, 0, 0, 0, 0, '');
        $fileNode       = new File('test.php', $root, 'abc123', [], [], [], [], [], [], new LinesOfCode(0, 0, 0));

        $node = new ClassNode('MyClass', '', '/test.php', 1, 5, $processedClass, $fileNode, [], [], $parent);

        // Empty string when no methods (0/0)
        $this->assertSame('', $node->percentageOfTestedClasses()->asString());
    }

    private function createClassNode(): ClassNode
    {
        $parent = new NamespaceNode('Models', 'App\\Models');
        $root   = new Directory('root');

        $method = new ProcessedMethodType('doSomething', 'public', 'public function doSomething(): void', 10, 50, 10, 7, 4, 2, 6, 3, 2, 70, 2, '');

        $processedClass = new ProcessedClassType('App\\Models\\User', 'App\\Models', ['doSomething' => $method], 10, 10, 7, 4, 2, 6, 3, 2, 70, 2, '');

        $fileNode = new File('User.php', $root, 'abc123', [], [], [], [], [], [], new LinesOfCode(0, 0, 0));

        return new ClassNode('App\\Models\\User', 'App\\Models', '/path/to/User.php', 10, 50, $processedClass, $fileNode, [], [], $parent);
    }

    private function createClassNodeWithName(string $className, string $namespace, NamespaceNode $parent): ClassNode
    {
        $root = new Directory('root');

        $method         = new ProcessedMethodType('m', 'public', 'public function m(): void', 1, 5, 3, 2, 0, 0, 0, 0, 1, 66, 1, '');
        $processedClass = new ProcessedClassType($className, $namespace, ['m' => $method], 1, 3, 2, 0, 0, 0, 0, 1, 66, 1, '');
        $fileNode       = new File('test.php', $root, 'abc123', [], [], [], [], [], [], new LinesOfCode(0, 0, 0));

        return new ClassNode($className, $namespace, '/test.php', 1, 10, $processedClass, $fileNode, [], [], $parent);
    }

    private function createClassNodeWithTrait(): ClassNode
    {
        $parent = new NamespaceNode('Root', '');
        $root   = new Directory('root');

        $ownMethod   = new ProcessedMethodType('doSomething', 'public', 'public function doSomething(): void', 1, 5, 10, 7, 4, 2, 6, 3, 1, 70, 1, '');
        $traitMethod = new ProcessedMethodType('traitMethod', 'public', 'public function traitMethod(): void', 1, 5, 5, 3, 2, 1, 3, 1, 1, 60, 1, '');

        $processedClass = new ProcessedClassType('App\\MyClass', 'App', ['doSomething' => $ownMethod], 1, 10, 7, 4, 2, 6, 3, 1, 70, 1, '');
        $processedTrait = new ProcessedTraitType('App\\MyTrait', 'App', ['traitMethod' => $traitMethod], 1, 5, 3, 2, 1, 3, 1, 1, 60, 1, '');

        $fileNode      = new File('test.php', $root, 'abc123', [], [], [], [], [], [], new LinesOfCode(0, 0, 0));
        $traitFileNode = new File('trait.php', $root, 'def456', [], [], [], [], [], [], new LinesOfCode(0, 0, 0));

        $traitSection = new TraitSection('App\\MyTrait', '/path/to/trait.php', 1, 10, $processedTrait, $traitFileNode);

        return new ClassNode('App\\MyClass', 'App', '/path/to/class.php', 1, 20, $processedClass, $fileNode, [$traitSection], [], $parent);
    }

    private function createClassNodeWithParent(): ClassNode
    {
        $parent = new NamespaceNode('Root', '');
        $root   = new Directory('root');

        $ownMethod       = new ProcessedMethodType('doSomething', 'public', 'public function doSomething(): void', 1, 5, 10, 7, 4, 2, 6, 3, 1, 70, 1, '');
        $inheritedMethod = new ProcessedMethodType('parentMethod', 'public', 'public function parentMethod(): void', 1, 5, 3, 2, 1, 0, 2, 1, 1, 66, 1, '');

        $processedClass = new ProcessedClassType('App\\MyClass', 'App', ['doSomething' => $ownMethod], 1, 10, 7, 4, 2, 6, 3, 1, 70, 1, '');

        $fileNode       = new File('test.php', $root, 'abc123', [], [], [], [], [], [], new LinesOfCode(0, 0, 0));
        $parentFileNode = new File('parent.php', $root, 'ghi789', [], [], [], [], [], [], new LinesOfCode(0, 0, 0));

        $parentSection = new ParentSection('App\\BaseClass', '/path/to/base.php', ['parentMethod' => $inheritedMethod], $parentFileNode);

        return new ClassNode('App\\MyClass', 'App', '/path/to/class.php', 1, 20, $processedClass, $fileNode, [], [$parentSection], $parent);
    }

    private function createClassNodeWithTraitAndParent(): ClassNode
    {
        $parent = new NamespaceNode('Root', '');
        $root   = new Directory('root');

        $ownMethod       = new ProcessedMethodType('doSomething', 'public', 'public function doSomething(): void', 1, 5, 10, 7, 4, 2, 6, 3, 1, 70, 1, '');
        $traitMethod     = new ProcessedMethodType('traitMethod', 'public', 'public function traitMethod(): void', 1, 5, 5, 3, 2, 1, 3, 1, 1, 60, 1, '');
        $inheritedMethod = new ProcessedMethodType('parentMethod', 'public', 'public function parentMethod(): void', 1, 5, 3, 2, 1, 0, 2, 1, 1, 66, 1, '');

        $processedClass = new ProcessedClassType('App\\MyClass', 'App', ['doSomething' => $ownMethod], 1, 10, 7, 4, 2, 6, 3, 1, 70, 1, '');
        $processedTrait = new ProcessedTraitType('App\\MyTrait', 'App', ['traitMethod' => $traitMethod], 1, 5, 3, 2, 1, 3, 1, 1, 60, 1, '');

        $fileNode       = new File('test.php', $root, 'abc123', [], [], [], [], [], [], new LinesOfCode(0, 0, 0));
        $traitFileNode  = new File('trait.php', $root, 'def456', [], [], [], [], [], [], new LinesOfCode(0, 0, 0));
        $parentFileNode = new File('parent.php', $root, 'ghi789', [], [], [], [], [], [], new LinesOfCode(0, 0, 0));

        $traitSection  = new TraitSection('App\\MyTrait', '/path/to/trait.php', 1, 10, $processedTrait, $traitFileNode);
        $parentSection = new ParentSection('App\\BaseClass', '/path/to/base.php', ['parentMethod' => $inheritedMethod], $parentFileNode);

        return new ClassNode('App\\MyClass', 'App', '/path/to/class.php', 1, 20, $processedClass, $fileNode, [$traitSection], [$parentSection], $parent);
    }
}
