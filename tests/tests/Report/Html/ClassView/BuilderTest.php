<?php declare(strict_types=1);
/*
 * This file is part of phpunit/php-code-coverage.
 *
 * (c) Sebastian Bergmann <sebastian@phpunit.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace SebastianBergmann\CodeCoverage\Report\Html\ClassView;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Small;
use PHPUnit\Framework\TestCase;
use SebastianBergmann\CodeCoverage\Node\Directory;
use SebastianBergmann\CodeCoverage\Node\File;
use SebastianBergmann\CodeCoverage\Report\Html\ClassView\Node\NamespaceNode;
use SebastianBergmann\CodeCoverage\StaticAnalysis\Class_;
use SebastianBergmann\CodeCoverage\StaticAnalysis\LinesOfCode;
use SebastianBergmann\CodeCoverage\StaticAnalysis\Method;
use SebastianBergmann\CodeCoverage\StaticAnalysis\Trait_;
use SebastianBergmann\CodeCoverage\StaticAnalysis\Visibility;

#[CoversClass(Builder::class)]
#[Small]
final class BuilderTest extends TestCase
{
    public function testBuildWithSingleClass(): void
    {
        $root = $this->createDirectoryWithClass('App\\MyClass', 'App');

        $builder = new Builder;
        $result  = $builder->build($root);

        $this->assertInstanceOf(NamespaceNode::class, $result);
        $this->assertCount(1, $result->classes());
        $this->assertSame('App\\MyClass', $result->classes()[0]->className());
    }

    public function testBuildWithNestedNamespaces(): void
    {
        $root = new Directory('root');

        $method   = new Method('doSomething', 1, 5, 'public function doSomething(): void', Visibility::Public, 1);
        $rawClass = new Class_('User', 'App\\Models\\User', 'App\\Models', '/path/to/User.php', 1, 20, null, [], [], ['doSomething' => $method]);

        $file = new File('User.php', $root, 'abc123', [], [], [], ['App\\Models\\User' => $rawClass], [], [], new LinesOfCode(20, 0, 20));
        $root->addFile($file);

        $builder = new Builder;
        $result  = $builder->build($root);

        $this->assertInstanceOf(NamespaceNode::class, $result);

        $allClasses = $result->allClassTypes();
        $this->assertArrayHasKey('App\\Models\\User', $allClasses);
    }

    public function testBuildWithTraitsResolution(): void
    {
        $root = new Directory('root');

        $traitMethod = new Method('traitMethod', 1, 5, 'public function traitMethod(): void', Visibility::Public, 1);
        $rawTrait    = new Trait_('MyTrait', 'App\\MyTrait', 'App', '/path/to/Trait.php', 1, 10, [], ['traitMethod' => $traitMethod]);

        $classMethod = new Method('doSomething', 10, 15, 'public function doSomething(): void', Visibility::Public, 1);
        $rawClass    = new Class_('MyClass', 'App\\MyClass', 'App', '/path/to/Class.php', 1, 20, null, [], ['App\\MyTrait'], ['doSomething' => $classMethod]);

        $traitFile = new File('Trait.php', $root, 'def456', [], [], [], [], ['App\\MyTrait' => $rawTrait], [], new LinesOfCode(10, 0, 10));
        $root->addFile($traitFile);

        $classFile = new File('Class.php', $root, 'abc123', [], [], [], ['App\\MyClass' => $rawClass], [], [], new LinesOfCode(20, 0, 20));
        $root->addFile($classFile);

        $builder = new Builder;
        $result  = $builder->build($root);

        $classes = [];

        foreach ($result->iterate() as $node) {
            if ($node instanceof Node\ClassNode) {
                $classes[] = $node;
            }
        }

        $this->assertNotEmpty($classes);
        $classNode = $classes[0];
        $this->assertCount(1, $classNode->traitSections());
        $this->assertSame('App\\MyTrait', $classNode->traitSections()[0]->traitName);
    }

    public function testBuildWithParentResolution(): void
    {
        $root = new Directory('root');

        $parentMethod = new Method('parentMethod', 1, 5, 'public function parentMethod(): void', Visibility::Public, 1);
        $rawParent    = new Class_('ParentClass', 'App\\ParentClass', 'App', '/path/to/Parent.php', 1, 10, null, [], [], ['parentMethod' => $parentMethod]);

        $childMethod = new Method('childMethod', 1, 5, 'public function childMethod(): void', Visibility::Public, 1);
        $rawChild    = new Class_('ChildClass', 'App\\ChildClass', 'App', '/path/to/Child.php', 1, 20, 'App\\ParentClass', [], [], ['childMethod' => $childMethod]);

        $parentFile = new File('Parent.php', $root, 'abc123', [], [], [], ['App\\ParentClass' => $rawParent], [], [], new LinesOfCode(10, 0, 10));
        $root->addFile($parentFile);

        $childFile = new File('Child.php', $root, 'def456', [], [], [], ['App\\ChildClass' => $rawChild], [], [], new LinesOfCode(20, 0, 20));
        $root->addFile($childFile);

        $builder = new Builder;
        $result  = $builder->build($root);

        $classNodes = [];

        foreach ($result->iterate() as $node) {
            if ($node instanceof Node\ClassNode) {
                $classNodes[$node->className()] = $node;
            }
        }

        $this->assertArrayHasKey('App\\ChildClass', $classNodes);
        $childNode = $classNodes['App\\ChildClass'];
        $this->assertCount(1, $childNode->parentSections());
        $this->assertSame('App\\ParentClass', $childNode->parentSections()[0]->className);
    }

    public function testBuildReducesRootWhenSingleChildNamespace(): void
    {
        $root = new Directory('root');

        $method   = new Method('m', 1, 5, 'public function m(): void', Visibility::Public, 1);
        $rawClass = new Class_('MyClass', 'A\\B\\C\\MyClass', 'A\\B\\C', '/path/to/MyClass.php', 1, 20, null, [], [], ['m' => $method]);

        $file = new File('MyClass.php', $root, 'abc123', [], [], [], ['A\\B\\C\\MyClass' => $rawClass], [], [], new LinesOfCode(20, 0, 20));
        $root->addFile($file);

        $builder = new Builder;
        $result  = $builder->build($root);

        // Root should be reduced past empty namespace levels
        $this->assertNull($result->parent());
    }

    public function testBuildDoesNotReduceRootWhenMultipleChildren(): void
    {
        $root = new Directory('root');

        $methodA = new Method('m', 1, 5, 'public function m(): void', Visibility::Public, 1);
        $methodB = new Method('m', 1, 5, 'public function m(): void', Visibility::Public, 1);

        $rawClassA = new Class_('ClassA', 'A\\ClassA', 'A', '/path/to/A.php', 1, 10, null, [], [], ['m' => $methodA]);
        $rawClassB = new Class_('ClassB', 'B\\ClassB', 'B', '/path/to/B.php', 1, 10, null, [], [], ['m' => $methodB]);

        $fileA = new File('A.php', $root, 'abc123', [], [], [], ['A\\ClassA' => $rawClassA], [], [], new LinesOfCode(10, 0, 10));
        $root->addFile($fileA);

        $fileB = new File('B.php', $root, 'def456', [], [], [], ['B\\ClassB' => $rawClassB], [], [], new LinesOfCode(10, 0, 10));
        $root->addFile($fileB);

        $builder = new Builder;
        $result  = $builder->build($root);

        // Root should have two child namespaces since A and B are different
        $this->assertCount(2, $result->childNamespaces());
    }

    public function testBuildWithGlobalNamespace(): void
    {
        $root = new Directory('root');

        $method   = new Method('m', 1, 5, 'public function m(): void', Visibility::Public, 1);
        $rawClass = new Class_('GlobalClass', 'GlobalClass', '', '/path/to/Global.php', 1, 10, null, [], [], ['m' => $method]);

        $file = new File('Global.php', $root, 'abc123', [], [], [], ['GlobalClass' => $rawClass], [], [], new LinesOfCode(10, 0, 10));
        $root->addFile($file);

        $builder = new Builder;
        $result  = $builder->build($root);

        $this->assertCount(1, $result->classes());
        $this->assertSame('GlobalClass', $result->classes()[0]->className());
    }

    public function testBuildSkipsTraitsNotInRegistry(): void
    {
        $root = new Directory('root');

        $classMethod = new Method('doSomething', 1, 5, 'public function doSomething(): void', Visibility::Public, 1);
        $rawClass    = new Class_('MyClass', 'App\\MyClass', 'App', '/path/to/Class.php', 1, 20, null, [], ['NonExistent\\Trait'], ['doSomething' => $classMethod]);

        $file = new File('Class.php', $root, 'abc123', [], [], [], ['App\\MyClass' => $rawClass], [], [], new LinesOfCode(20, 0, 20));
        $root->addFile($file);

        $builder = new Builder;
        $result  = $builder->build($root);

        $classes = [];

        foreach ($result->iterate() as $node) {
            if ($node instanceof Node\ClassNode) {
                $classes[] = $node;
            }
        }

        $this->assertNotEmpty($classes);
        $this->assertCount(0, $classes[0]->traitSections());
    }

    public function testBuildSkipsParentsNotInRegistry(): void
    {
        $root = new Directory('root');

        $method   = new Method('m', 1, 5, 'public function m(): void', Visibility::Public, 1);
        $rawClass = new Class_('MyClass', 'App\\MyClass', 'App', '/path/to/Class.php', 1, 20, 'NonExistent\\Parent', [], [], ['m' => $method]);

        $file = new File('Class.php', $root, 'abc123', [], [], [], ['App\\MyClass' => $rawClass], [], [], new LinesOfCode(20, 0, 20));
        $root->addFile($file);

        $builder = new Builder;
        $result  = $builder->build($root);

        $classes = [];

        foreach ($result->iterate() as $node) {
            if ($node instanceof Node\ClassNode) {
                $classes[] = $node;
            }
        }

        $this->assertNotEmpty($classes);
        $this->assertCount(0, $classes[0]->parentSections());
    }

    public function testBuildInheritanceSkipsOverriddenMethods(): void
    {
        $root = new Directory('root');

        $parentSharedMethod = new Method('sharedMethod', 1, 5, 'public function sharedMethod(): void', Visibility::Public, 1);
        $rawParent          = new Class_('ParentClass', 'App\\ParentClass', 'App', '/path/to/Parent.php', 1, 10, null, [], [], ['sharedMethod' => $parentSharedMethod]);

        // Child overrides sharedMethod
        $childSharedMethod = new Method('sharedMethod', 1, 5, 'public function sharedMethod(): void', Visibility::Public, 1);
        $rawChild          = new Class_('ChildClass', 'App\\ChildClass', 'App', '/path/to/Child.php', 1, 20, 'App\\ParentClass', [], [], ['sharedMethod' => $childSharedMethod]);

        $parentFile = new File('Parent.php', $root, 'abc123', [], [], [], ['App\\ParentClass' => $rawParent], [], [], new LinesOfCode(10, 0, 10));
        $root->addFile($parentFile);

        $childFile = new File('Child.php', $root, 'def456', [], [], [], ['App\\ChildClass' => $rawChild], [], [], new LinesOfCode(20, 0, 20));
        $root->addFile($childFile);

        $builder = new Builder;
        $result  = $builder->build($root);

        $classNodes = [];

        foreach ($result->iterate() as $node) {
            if ($node instanceof Node\ClassNode) {
                $classNodes[$node->className()] = $node;
            }
        }

        $this->assertArrayHasKey('App\\ChildClass', $classNodes);
        $childNode = $classNodes['App\\ChildClass'];
        // sharedMethod is overridden, so no parent sections with inherited methods
        $this->assertCount(0, $childNode->parentSections());
    }

    public function testBuildWithSubdirectory(): void
    {
        $root   = new Directory('root');
        $subDir = $root->addDirectory('sub');

        $method   = new Method('m', 1, 5, 'public function m(): void', Visibility::Public, 1);
        $rawClass = new Class_('MyClass', 'App\\MyClass', 'App', '/path/to/MyClass.php', 1, 10, null, [], [], ['m' => $method]);

        $file = new File('MyClass.php', $subDir, 'abc123', [], [], [], ['App\\MyClass' => $rawClass], [], [], new LinesOfCode(10, 0, 10));
        $subDir->addFile($file);

        $builder = new Builder;
        $result  = $builder->build($root);

        $allClasses = $result->allClassTypes();
        $this->assertArrayHasKey('App\\MyClass', $allClasses);
    }

    public function testBuildCanBeCalledMultipleTimes(): void
    {
        $root = $this->createDirectoryWithClass('App\\MyClass', 'App');

        $builder = new Builder;
        $result1 = $builder->build($root);
        $result2 = $builder->build($root);

        $this->assertCount(1, $result1->classes());
        $this->assertCount(1, $result2->classes());
    }

    private function createDirectoryWithClass(string $className, string $namespace): Directory
    {
        $root = new Directory('root');

        $method   = new Method('doSomething', 1, 5, 'public function doSomething(): void', Visibility::Public, 1);
        $rawClass = new Class_('MyClass', $className, $namespace, '/path/to/Class.php', 1, 20, null, [], [], ['doSomething' => $method]);

        $file = new File('Class.php', $root, 'abc123', [], [], [], [$className => $rawClass], [], [], new LinesOfCode(20, 0, 20));
        $root->addFile($file);

        return $root;
    }
}
