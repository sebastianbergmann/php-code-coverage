<?php declare(strict_types=1);
/*
 * This file is part of phpunit/php-code-coverage.
 *
 * (c) Sebastian Bergmann <sebastian@phpunit.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace SebastianBergmann\CodeCoverage\Node;

use function array_fill;
use function str_replace;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Small;
use PHPUnit\Framework\TestCase;
use SebastianBergmann\CodeCoverage\StaticAnalysis\Class_;
use SebastianBergmann\CodeCoverage\StaticAnalysis\Function_;
use SebastianBergmann\CodeCoverage\StaticAnalysis\LinesOfCode;
use SebastianBergmann\CodeCoverage\StaticAnalysis\Method;
use SebastianBergmann\CodeCoverage\StaticAnalysis\Trait_;
use SebastianBergmann\CodeCoverage\StaticAnalysis\Visibility;

#[CoversClass(Directory::class)]
#[Small]
final class DirectoryTest extends TestCase
{
    public function testCountWithNoChildren(): void
    {
        $root = new Directory('root');

        $this->assertCount(0, $root);
    }

    public function testCountWithFiles(): void
    {
        $root = new Directory('root');

        $root->addFile($this->createFile($root, 'a.php'));
        $root->addFile($this->createFile($root, 'b.php'));

        $this->assertCount(2, $root);
    }

    public function testCountWithNestedDirectories(): void
    {
        $root  = new Directory('root');
        $child = $root->addDirectory('sub');

        $child->addFile($this->createFile($child, 'a.php'));

        $this->assertCount(1, $root);
    }

    public function testAddDirectoryReturnsNewDirectory(): void
    {
        $root  = new Directory('root');
        $child = $root->addDirectory('sub');

        $this->assertSame('sub', $child->name());
        $this->assertCount(1, $root->directories());
        $this->assertCount(1, $root->children());
    }

    public function testFunctionsAggregatesFromChildren(): void
    {
        $root = new Directory('root');

        $function = new Function_(
            'myFunc',
            'myFunc',
            '',
            1,
            5,
            'function myFunc(): void',
            1,
        );

        $file = new File(
            'test.php',
            $root,
            'abc123',
            [1 => ['test1']],
            [],
            ['test1' => ['size' => 'small', 'status' => 'passed', 'time' => 0.0]],
            [],
            [],
            ['myFunc' => $function],
            new LinesOfCode(5, 0, 5),
        );

        $root->addFile($file);

        $this->assertArrayHasKey('myFunc', $root->functions());
    }

    public function testFunctionsAreCached(): void
    {
        $root = new Directory('root');

        $first  = $root->functions();
        $second = $root->functions();

        $this->assertSame($first, $second);
    }

    public function testNumberOfFilesWithoutBranchCoverageDataWithNoChildren(): void
    {
        $root = new Directory('root');

        $this->assertSame(0, $root->numberOfFilesWithoutBranchCoverageData());
    }

    public function testNumberOfFilesWithoutBranchCoverageDataWithAllFilesHavingData(): void
    {
        $root = new Directory('root');

        $root->addFile($this->createFileWithBranchCoverageData($root, 'a.php'));
        $root->addFile($this->createFileWithBranchCoverageData($root, 'b.php'));

        $this->assertSame(0, $root->numberOfFilesWithoutBranchCoverageData());
    }

    public function testNumberOfFilesWithoutBranchCoverageDataWithSomeFilesMissingData(): void
    {
        $root = new Directory('root');

        $root->addFile($this->createFileWithBranchCoverageData($root, 'a.php'));
        $root->addFile($this->createFile($root, 'b.php'));
        $root->addFile($this->createFile($root, 'c.php'));

        $this->assertSame(2, $root->numberOfFilesWithoutBranchCoverageData());
    }

    public function testNumberOfFilesWithoutBranchCoverageDataAggregatesFromNestedDirectories(): void
    {
        $root  = new Directory('root');
        $child = $root->addDirectory('sub');

        $root->addFile($this->createFileWithBranchCoverageData($root, 'a.php'));
        $child->addFile($this->createFile($child, 'b.php'));

        $this->assertSame(1, $root->numberOfFilesWithoutBranchCoverageData());
    }

    public function testFilesReturnsAddedFiles(): void
    {
        $root = new Directory('root');

        $root->addFile($this->createFile($root, 'a.php'));
        $root->addFile($this->createFile($root, 'b.php'));

        $files = $root->files();

        $this->assertCount(2, $files);
    }

    public function testIsIterable(): void
    {
        $root  = new Directory('root');
        $child = $root->addDirectory('sub');

        $root->addFile($this->createFile($root, 'a.php'));
        $child->addFile($this->createFile($child, 'b.php'));

        $nodes = [];

        foreach ($root->getIterator() as $node) {
            $nodes[] = $node;
        }

        $this->assertNotEmpty($nodes);
    }

    public function testClassesAggregatesFromChildren(): void
    {
        $root  = new Directory('root');
        $child = $root->addDirectory('sub');

        $root->addFile($this->createPopulatedFile($root, 'a.php'));
        $child->addFile($this->createPopulatedFile($child, 'b.php'));

        $classes = $root->classes();

        $this->assertCount(2, $classes);
    }

    public function testClassesAreCached(): void
    {
        $root = new Directory('root');

        $this->assertSame($root->classes(), $root->classes());
    }

    public function testTraitsAggregatesFromChildren(): void
    {
        $root  = new Directory('root');
        $child = $root->addDirectory('sub');

        $root->addFile($this->createPopulatedFile($root, 'a.php'));
        $child->addFile($this->createPopulatedFile($child, 'b.php'));

        $traits = $root->traits();

        $this->assertCount(2, $traits);
    }

    public function testTraitsAreCached(): void
    {
        $root = new Directory('root');

        $this->assertSame($root->traits(), $root->traits());
    }

    public function testLinesOfCodeAggregatesFromChildren(): void
    {
        $root  = new Directory('root');
        $child = $root->addDirectory('sub');

        $root->addFile($this->createPopulatedFile($root, 'a.php'));
        $child->addFile($this->createPopulatedFile($child, 'b.php'));

        $linesOfCode = $root->linesOfCode();

        $this->assertSame(30, $linesOfCode->linesOfCode());
        $this->assertSame(30, $linesOfCode->nonCommentLinesOfCode());

        $this->assertSame($linesOfCode, $root->linesOfCode());
    }

    public function testNumberOfExecutableAndExecutedLinesAggregatesFromChildren(): void
    {
        $root  = new Directory('root');
        $child = $root->addDirectory('sub');

        $root->addFile($this->createPopulatedFile($root, 'a.php'));
        $child->addFile($this->createPopulatedFile($child, 'b.php'));

        $this->assertSame(30, $root->numberOfExecutableLines());
        $this->assertSame(30, $root->numberOfExecutedLines());

        // second call exercises the cache
        $this->assertSame(30, $root->numberOfExecutableLines());
        $this->assertSame(30, $root->numberOfExecutedLines());
    }

    public function testNumberOfExecutableAndExecutedBranchesAggregatesFromChildren(): void
    {
        $root = new Directory('root');

        $root->addFile($this->createPopulatedFile($root, 'a.php'));

        $this->assertSame(0, $root->numberOfExecutableBranches());
        $this->assertSame(0, $root->numberOfExecutedBranches());

        $this->assertSame(0, $root->numberOfExecutableBranches());
        $this->assertSame(0, $root->numberOfExecutedBranches());
    }

    public function testNumberOfExecutableAndExecutedPathsAggregatesFromChildren(): void
    {
        $root = new Directory('root');

        $root->addFile($this->createPopulatedFile($root, 'a.php'));

        $this->assertSame(0, $root->numberOfExecutablePaths());
        $this->assertSame(0, $root->numberOfExecutedPaths());

        $this->assertSame(0, $root->numberOfExecutablePaths());
        $this->assertSame(0, $root->numberOfExecutedPaths());
    }

    public function testNumberOfClassesAndTestedClassesAggregatesFromChildren(): void
    {
        $root  = new Directory('root');
        $child = $root->addDirectory('sub');

        $root->addFile($this->createPopulatedFile($root, 'a.php'));
        $child->addFile($this->createPopulatedFile($child, 'b.php'));

        $this->assertSame(2, $root->numberOfClasses());
        $this->assertSame(2, $root->numberOfTestedClasses());

        $this->assertSame(2, $root->numberOfClasses());
        $this->assertSame(2, $root->numberOfTestedClasses());
    }

    public function testNumberOfTraitsAndTestedTraitsAggregatesFromChildren(): void
    {
        $root  = new Directory('root');
        $child = $root->addDirectory('sub');

        $root->addFile($this->createPopulatedFile($root, 'a.php'));
        $child->addFile($this->createPopulatedFile($child, 'b.php'));

        $this->assertSame(2, $root->numberOfTraits());
        $this->assertSame(2, $root->numberOfTestedTraits());

        $this->assertSame(2, $root->numberOfTraits());
        $this->assertSame(2, $root->numberOfTestedTraits());
    }

    public function testNumberOfMethodsAndTestedMethodsAggregatesFromChildren(): void
    {
        $root  = new Directory('root');
        $child = $root->addDirectory('sub');

        $root->addFile($this->createPopulatedFile($root, 'a.php'));
        $child->addFile($this->createPopulatedFile($child, 'b.php'));

        $this->assertSame(4, $root->numberOfMethods());
        $this->assertSame(4, $root->numberOfTestedMethods());

        $this->assertSame(4, $root->numberOfMethods());
        $this->assertSame(4, $root->numberOfTestedMethods());
    }

    public function testNumberOfFunctionsAndTestedFunctionsAggregatesFromChildren(): void
    {
        $root  = new Directory('root');
        $child = $root->addDirectory('sub');

        $root->addFile($this->createPopulatedFile($root, 'a.php'));
        $child->addFile($this->createPopulatedFile($child, 'b.php'));

        $this->assertSame(2, $root->numberOfFunctions());
        $this->assertSame(2, $root->numberOfTestedFunctions());

        $this->assertSame(2, $root->numberOfFunctions());
        $this->assertSame(2, $root->numberOfTestedFunctions());
    }

    /**
     * @param non-empty-string $name
     */
    private function createPopulatedFile(Directory $parent, string $name): File
    {
        // Derive unique code unit names so that aggregation across files does
        // not collapse same-named units when merging keyed arrays.
        $suffix = str_replace('.', '_', $name);

        $classMethod = new Method(
            'classMethod',
            1,
            5,
            'public function classMethod(): void',
            Visibility::Public,
            1,
        );

        $class = new Class_(
            'MyClass_' . $suffix,
            'MyClass_' . $suffix,
            '',
            $name,
            1,
            5,
            null,
            [],
            [],
            ['classMethod' => $classMethod],
        );

        $traitMethod = new Method(
            'traitMethod',
            6,
            10,
            'public function traitMethod(): void',
            Visibility::Public,
            1,
        );

        $trait = new Trait_(
            'MyTrait_' . $suffix,
            'MyTrait_' . $suffix,
            '',
            $name,
            6,
            10,
            [],
            ['traitMethod' => $traitMethod],
        );

        $function = new Function_(
            'myFunc_' . $suffix,
            'myFunc_' . $suffix,
            '',
            11,
            15,
            'function myFunc(): void',
            1,
        );

        $lineCoverageData = array_fill(1, 15, ['test1']);

        return new File(
            $name,
            $parent,
            'sha1hash',
            $lineCoverageData,
            [],
            ['test1'              => ['size' => 'small', 'status' => 'passed', 'time' => 0.0]],
            ['MyClass_' . $suffix => $class],
            ['MyTrait_' . $suffix => $trait],
            ['myFunc_' . $suffix  => $function],
            new LinesOfCode(15, 0, 15),
        );
    }

    private function createFileWithBranchCoverageData(Directory $parent, string $name): File
    {
        return new File(
            $name,
            $parent,
            'sha1hash',
            [],
            [],
            [],
            [],
            [],
            [],
            new LinesOfCode(0, 0, 0),
            true,
        );
    }

    private function createFile(Directory $parent, string $name): File
    {
        return new File(
            $name,
            $parent,
            'sha1hash',
            [],
            [],
            [],
            [],
            [],
            [],
            new LinesOfCode(0, 0, 0),
        );
    }
}
