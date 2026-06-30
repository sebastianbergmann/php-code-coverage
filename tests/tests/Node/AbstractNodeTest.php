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

use const DIRECTORY_SEPARATOR;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Small;
use PHPUnit\Framework\TestCase;
use SebastianBergmann\CodeCoverage\StaticAnalysis\Class_;
use SebastianBergmann\CodeCoverage\StaticAnalysis\Function_;
use SebastianBergmann\CodeCoverage\StaticAnalysis\LinesOfCode;
use SebastianBergmann\CodeCoverage\StaticAnalysis\Method;
use SebastianBergmann\CodeCoverage\StaticAnalysis\Trait_;
use SebastianBergmann\CodeCoverage\StaticAnalysis\Visibility;

#[CoversClass(AbstractNode::class)]
#[Small]
final class AbstractNodeTest extends TestCase
{
    public function testParentReturnsNullForRoot(): void
    {
        $root = new Directory('root');

        $this->assertNull($root->parent());
    }

    public function testParentReturnsParentNode(): void
    {
        $root  = new Directory('root');
        $child = $root->addDirectory('child');

        $this->assertSame($root, $child->parent());
    }

    public function testPercentageOfTestedClasses(): void
    {
        $file       = $this->createFileWithTestedClass();
        $percentage = $file->percentageOfTestedClasses();

        $this->assertSame('100.00%', $percentage->asString());
    }

    public function testPercentageOfTestedTraits(): void
    {
        $file       = $this->createFileWithTestedTrait();
        $percentage = $file->percentageOfTestedTraits();

        $this->assertIsString($percentage->asString());
    }

    public function testPercentageOfTestedFunctions(): void
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
            [
                1 => ['test1'],
                2 => ['test1'],
                3 => ['test1'],
                4 => ['test1'],
                5 => ['test1'],
            ],
            [],
            ['test1' => ['size' => 'small', 'status' => 'passed', 'time' => 0.0]],
            [],
            [],
            ['myFunc' => $function],
            new LinesOfCode(5, 0, 5),
        );

        $percentage = $file->percentageOfTestedFunctions();
        $this->assertIsString($percentage->asString());
    }

    public function testPercentageOfTestedMethods(): void
    {
        $file       = $this->createFileWithTestedClass();
        $percentage = $file->percentageOfTestedMethods();

        $this->assertIsString($percentage->asString());
    }

    public function testCyclomaticComplexity(): void
    {
        $file = $this->createFileWithTestedClass();
        $ccn  = $file->cyclomaticComplexity();

        $this->assertGreaterThanOrEqual(0, $ccn);
    }

    public function testCyclomaticComplexityWithFunctions(): void
    {
        $root = new Directory('root');

        $function = new Function_(
            'myFunc',
            'myFunc',
            '',
            1,
            5,
            'function myFunc(): void',
            3,
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

        $this->assertGreaterThan(0, $file->cyclomaticComplexity());
    }

    public function testNumberOfClassesAndTraits(): void
    {
        $file = $this->createFileWithTestedClass();

        $this->assertSame(1, $file->numberOfClassesAndTraits());
    }

    public function testNumberOfTestedClassesAndTraits(): void
    {
        $file = $this->createFileWithTestedClass();

        $this->assertSame(1, $file->numberOfTestedClassesAndTraits());
    }

    public function testNumberOfFunctionsAndMethods(): void
    {
        $file = $this->createFileWithTestedClass();

        $this->assertSame(1, $file->numberOfFunctionsAndMethods());
    }

    public function testNumberOfTestedFunctionsAndMethods(): void
    {
        $file = $this->createFileWithTestedClass();

        $this->assertSame(1, $file->numberOfTestedFunctionsAndMethods());
    }

    public function testPercentageOfTestedClassesAndTraits(): void
    {
        $file = $this->createFileWithTestedClass();

        $this->assertSame('100.00%', $file->percentageOfTestedClassesAndTraits()->asString());
    }

    public function testPercentageOfTestedFunctionsAndMethods(): void
    {
        $file = $this->createFileWithTestedClass();

        $this->assertSame('100.00%', $file->percentageOfTestedFunctionsAndMethods()->asString());
    }

    public function testPercentageOfExecutedLines(): void
    {
        $file = $this->createFileWithTestedClass();

        $this->assertSame('100.00%', $file->percentageOfExecutedLines()->asString());
    }

    public function testPercentageOfExecutedBranches(): void
    {
        $file = $this->createFileWithTestedClass();

        $this->assertIsString($file->percentageOfExecutedBranches()->asString());
    }

    public function testPercentageOfExecutedPaths(): void
    {
        $file = $this->createFileWithTestedClass();

        $this->assertIsString($file->percentageOfExecutedPaths()->asString());
    }

    public function testClassesAndTraitsAreMerged(): void
    {
        $file = $this->createFileWithTestedClass();

        $classesAndTraits = $file->classesAndTraits();

        $this->assertArrayHasKey('MyClass', $classesAndTraits);
    }

    public function testPathAsStringContainsAllAncestors(): void
    {
        $root       = new Directory('root');
        $child      = $root->addDirectory('child');
        $grandchild = $child->addDirectory('grandchild');

        $this->assertSame(
            'root' . DIRECTORY_SEPARATOR . 'child' . DIRECTORY_SEPARATOR . 'grandchild',
            $grandchild->pathAsString(),
        );
    }

    public function testPathAsArrayContainsAllAncestors(): void
    {
        $root       = new Directory('root');
        $child      = $root->addDirectory('child');
        $grandchild = $child->addDirectory('grandchild');

        $path = $grandchild->pathAsArray();

        $this->assertCount(3, $path);
        $this->assertSame($root, $path[0]);
        $this->assertSame($child, $path[1]);
        $this->assertSame($grandchild, $path[2]);
    }

    public function testPercentageOfExecutedLinesByTestSize(): void
    {
        $file = $this->createFileWithMixedTestSizeCoverage();

        $this->assertSame(50.0, $file->percentageOfExecutedLinesBySmallTests()->asFloat());
        $this->assertSame(25.0, $file->percentageOfExecutedLinesByMediumTests()->asFloat());
        $this->assertSame(25.0, $file->percentageOfExecutedLinesByLargeTests()->asFloat());
        $this->assertSame(75.0, $file->percentageOfExecutedLinesBySmallOrMediumTests()->asFloat());
        $this->assertSame(75.0, $file->percentageOfExecutedLinesBySmallOrLargeTests()->asFloat());
        $this->assertSame(50.0, $file->percentageOfExecutedLinesByMediumOrLargeTests()->asFloat());
        $this->assertSame(100.0, $file->percentageOfExecutedLinesBySmallOrMediumOrLargeTests()->asFloat());
    }

    public function testNumberOfTestedClassesAndTraitsByTestSize(): void
    {
        $file = $this->createFileWithMixedTestSizeCoverage();

        $this->assertSame(1, $file->numberOfTestedClassesAndTraitsBySmallTests());
        $this->assertSame(1, $file->numberOfTestedClassesAndTraitsByMediumTests());
        $this->assertSame(0, $file->numberOfTestedClassesAndTraitsByLargeTests());
        $this->assertSame(2, $file->numberOfTestedClassesAndTraitsBySmallOrMediumTests());
        $this->assertSame(1, $file->numberOfTestedClassesAndTraitsBySmallOrLargeTests());
        $this->assertSame(1, $file->numberOfTestedClassesAndTraitsByMediumOrLargeTests());
        $this->assertSame(2, $file->numberOfTestedClassesAndTraitsBySmallOrMediumOrLargeTests());
    }

    public function testNumberOfTestedFunctionsAndMethodsByTestSize(): void
    {
        $file = $this->createFileWithMixedTestSizeCoverage();

        $this->assertSame(1, $file->numberOfTestedFunctionsAndMethodsBySmallTests());
        $this->assertSame(1, $file->numberOfTestedFunctionsAndMethodsByMediumTests());
        $this->assertSame(1, $file->numberOfTestedFunctionsAndMethodsByLargeTests());
        $this->assertSame(2, $file->numberOfTestedFunctionsAndMethodsBySmallOrMediumTests());
        $this->assertSame(2, $file->numberOfTestedFunctionsAndMethodsBySmallOrLargeTests());
        $this->assertSame(2, $file->numberOfTestedFunctionsAndMethodsByMediumOrLargeTests());
        $this->assertSame(3, $file->numberOfTestedFunctionsAndMethodsBySmallOrMediumOrLargeTests());
    }

    public function testNameStripsTrailingDirectorySeparator(): void
    {
        $root = new Directory('root' . DIRECTORY_SEPARATOR);

        $this->assertSame('root', $root->name());
    }

    public function testIdForGrandchild(): void
    {
        $root       = new Directory('root');
        $child      = $root->addDirectory('child');
        $grandchild = $child->addDirectory('grandchild');

        $this->assertSame('child/grandchild', $grandchild->id());
    }

    private function createFileWithMixedTestSizeCoverage(): File
    {
        $root = new Directory('root');

        $smallMethod  = new Method('m1', 1, 4, 'public function m1(): void', Visibility::Public, 1);
        $mediumMethod = new Method('m2', 5, 6, 'public function m2(): void', Visibility::Public, 1);

        $smallClass  = new Class_('SmallClass', 'SmallClass', '', 'test.php', 1, 4, null, [], [], ['m1' => $smallMethod]);
        $mediumTrait = new Trait_('MediumTrait', 'MediumTrait', '', 'test.php', 5, 6, [], ['m2' => $mediumMethod]);
        $largeFunc   = new Function_('largeFunc', 'largeFunc', '', 7, 8, 'function largeFunc(): void', 1);

        $lineCoverageData = [
            1 => ['tSmall'],
            2 => ['tSmall'],
            3 => ['tSmall'],
            4 => ['tSmall'],
            5 => ['tMedium'],
            6 => ['tMedium'],
            7 => ['tLarge'],
            8 => ['tLarge'],
        ];

        $testData = [
            'tSmall'  => ['size' => 'small', 'status' => 'passed', 'time' => 0.0],
            'tMedium' => ['size' => 'medium', 'status' => 'passed', 'time' => 0.0],
            'tLarge'  => ['size' => 'large', 'status' => 'passed', 'time' => 0.0],
        ];

        return new File(
            'test.php',
            $root,
            'abc123',
            $lineCoverageData,
            [],
            $testData,
            ['SmallClass'  => $smallClass],
            ['MediumTrait' => $mediumTrait],
            ['largeFunc'   => $largeFunc],
            new LinesOfCode(8, 0, 8),
        );
    }

    private function createFileWithTestedClass(): File
    {
        $root = new Directory('root');

        $method = new Method(
            'testMethod',
            1,
            5,
            'public function testMethod(): void',
            Visibility::Public,
            1,
        );

        $class = new Class_(
            'MyClass',
            'MyClass',
            '',
            'test.php',
            1,
            5,
            null,
            [],
            [],
            ['testMethod' => $method],
        );

        return new File(
            'test.php',
            $root,
            'abc123',
            [
                1 => ['test1'],
                2 => ['test1'],
                3 => ['test1'],
                4 => ['test1'],
                5 => ['test1'],
            ],
            [],
            ['test1'   => ['size' => 'small', 'status' => 'passed', 'time' => 0.0]],
            ['MyClass' => $class],
            [],
            [],
            new LinesOfCode(5, 0, 5),
        );
    }

    private function createFileWithTestedTrait(): File
    {
        $root = new Directory('root');

        $method = new Method(
            'traitMethod',
            1,
            5,
            'public function traitMethod(): void',
            Visibility::Public,
            1,
        );

        $trait = new Trait_(
            'MyTrait',
            'MyTrait',
            '',
            'test.php',
            1,
            5,
            [],
            ['traitMethod' => $method],
        );

        return new File(
            'test.php',
            $root,
            'abc123',
            [
                1 => ['test1'],
                2 => ['test1'],
                3 => ['test1'],
                4 => ['test1'],
                5 => ['test1'],
            ],
            [],
            ['test1' => ['size' => 'small', 'status' => 'passed', 'time' => 0.0]],
            [],
            ['MyTrait' => $trait],
            [],
            new LinesOfCode(5, 0, 5),
        );
    }
}
