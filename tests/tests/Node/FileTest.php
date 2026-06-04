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
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Small;
use PHPUnit\Framework\TestCase;
use SebastianBergmann\CodeCoverage\Data\ProcessedBranchCoverageData;
use SebastianBergmann\CodeCoverage\Data\ProcessedFunctionCoverageData;
use SebastianBergmann\CodeCoverage\Data\ProcessedPathCoverageData;
use SebastianBergmann\CodeCoverage\StaticAnalysis\Class_;
use SebastianBergmann\CodeCoverage\StaticAnalysis\Function_;
use SebastianBergmann\CodeCoverage\StaticAnalysis\LinesOfCode;
use SebastianBergmann\CodeCoverage\StaticAnalysis\Method;
use SebastianBergmann\CodeCoverage\StaticAnalysis\Trait_;
use SebastianBergmann\CodeCoverage\StaticAnalysis\Visibility;

#[CoversClass(File::class)]
#[Small]
final class FileTest extends TestCase
{
    public function testCountReturnsOne(): void
    {
        $file = $this->createFileNode();

        $this->assertCount(1, $file);
    }

    public function testNumberOfTraitsWithTraits(): void
    {
        $file = $this->createFileNodeWithTrait();

        $this->assertSame(1, $file->numberOfTraits());
    }

    public function testNumberOfMethodsIncludesTraitMethods(): void
    {
        $file = $this->createFileNodeWithTrait();

        $this->assertGreaterThanOrEqual(1, $file->numberOfMethods());
    }

    public function testNumberOfTestedMethodsIncludesTraitMethods(): void
    {
        $file = $this->createFileNodeWithTrait();

        $this->assertIsInt($file->numberOfTestedMethods());
    }

    public function testNumberOfTestedFunctions(): void
    {
        $file = $this->createFileNodeWithFunction();

        $this->assertIsInt($file->numberOfTestedFunctions());
    }

    public function testNumberOfTestedFunctionsWithFullCoverage(): void
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

        $lineCoverageData = [
            1 => ['test1'],
            2 => ['test1'],
            3 => ['test1'],
            4 => ['test1'],
            5 => ['test1'],
        ];

        $file = new File(
            'test.php',
            $root,
            'abc123',
            $lineCoverageData,
            [],
            ['test1' => ['size' => 'small', 'status' => 'passed', 'time' => 0.0]],
            [],
            [],
            ['myFunc' => $function],
            new LinesOfCode(5, 0, 5),
        );

        $this->assertSame(1, $file->numberOfTestedFunctions());
    }

    public function testProcessTraits(): void
    {
        $file = $this->createFileNodeWithTrait();

        $traits = $file->traits();

        $this->assertNotEmpty($traits);
        $this->assertArrayHasKey('MyTrait', $traits);

        $trait = $traits['MyTrait'];

        $this->assertNotEmpty($trait->methods);
        $this->assertArrayHasKey('traitMethod', $trait->methods);
    }

    public function testTraitCoverageStatistics(): void
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

        $lineCoverageData = [
            1 => ['test1'],
            2 => ['test1'],
            3 => ['test1'],
            4 => ['test1'],
            5 => ['test1'],
        ];

        $file = new File(
            'test.php',
            $root,
            'abc123',
            $lineCoverageData,
            [],
            ['test1' => ['size' => 'small', 'status' => 'passed', 'time' => 0.0]],
            [],
            ['MyTrait' => $trait],
            [],
            new LinesOfCode(5, 0, 5),
        );

        $traits = $file->traits();

        $this->assertArrayHasKey('MyTrait', $traits);

        $traitData = $traits['MyTrait'];

        $this->assertGreaterThan(0, $traitData->executableLines);
        $this->assertGreaterThan(0, $traitData->executedLines);
    }

    public function testTraitWithNoExecutableLinesNotCounted(): void
    {
        $root = new Directory('root');

        $method = new Method(
            'traitMethod',
            1,
            1,
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
            1,
            [],
            ['traitMethod' => $method],
        );

        $file = new File(
            'test.php',
            $root,
            'abc123',
            [],
            [],
            [],
            [],
            ['MyTrait' => $trait],
            [],
            new LinesOfCode(1, 0, 1),
        );

        $this->assertSame(0, $file->numberOfTraits());
    }

    public function testTestedTraitsCount(): void
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

        $lineCoverageData = [
            1 => ['test1'],
            2 => ['test1'],
            3 => ['test1'],
            4 => ['test1'],
            5 => ['test1'],
        ];

        $file = new File(
            'test.php',
            $root,
            'abc123',
            $lineCoverageData,
            [],
            ['test1' => ['size' => 'small', 'status' => 'passed', 'time' => 0.0]],
            [],
            ['MyTrait' => $trait],
            [],
            new LinesOfCode(5, 0, 5),
        );

        $this->assertSame(1, $file->numberOfTestedTraits());
    }

    public function testHasBranchCoverageDataDefaultsToFalse(): void
    {
        $file = $this->createFileNode();

        $this->assertFalse($file->hasBranchCoverageData());
        $this->assertSame(1, $file->numberOfFilesWithoutBranchCoverageData());
    }

    public function testHasBranchCoverageDataWhenTrue(): void
    {
        $root = new Directory('root');

        $file = new File(
            'test.php',
            $root,
            'abc123',
            [],
            [],
            [],
            [],
            [],
            [],
            new LinesOfCode(0, 0, 0),
            true,
        );

        $this->assertTrue($file->hasBranchCoverageData());
        $this->assertSame(0, $file->numberOfFilesWithoutBranchCoverageData());
    }

    public function testExposesSha1(): void
    {
        $file = $this->createFileNode();

        $this->assertSame('abc123', $file->sha1());
    }

    public function testExposesLineCoverageData(): void
    {
        $file = $this->createFileNodeWithFunction();

        $this->assertArrayHasKey(1, $file->lineCoverageData());
    }

    public function testExposesFunctionCoverageData(): void
    {
        $file = $this->createFileNodeWithCoverageData();

        $this->assertArrayHasKey('myFunc', $file->functionCoverageData());
    }

    public function testExposesTestData(): void
    {
        $file = $this->createFileNodeWithFunction();

        $this->assertArrayHasKey('test1', $file->testData());
    }

    public function testExposesClasses(): void
    {
        $file = $this->createFileNodeWithCoverageData();

        $this->assertArrayHasKey('MyClass', $file->classes());
    }

    public function testExposesFunctions(): void
    {
        $file = $this->createFileNodeWithFunction();

        $this->assertArrayHasKey('myFunc', $file->functions());
    }

    public function testExposesLinesOfCode(): void
    {
        $file = $this->createFileNodeWithFunction();

        $this->assertSame(5, $file->linesOfCode()->linesOfCode());
    }

    public function testCountsExecutableAndExecutedLines(): void
    {
        $file = $this->createFileNodeWithFunction();

        $this->assertSame(2, $file->numberOfExecutableLines());
        $this->assertSame(1, $file->numberOfExecutedLines());
    }

    public function testCountsExecutableAndExecutedBranches(): void
    {
        $file = $this->createFileNodeWithCoverageData();

        $this->assertSame(2, $file->numberOfExecutableBranches());
        $this->assertSame(2, $file->numberOfExecutedBranches());
    }

    public function testCountsExecutableAndExecutedPaths(): void
    {
        $file = $this->createFileNodeWithCoverageData();

        $this->assertSame(2, $file->numberOfExecutablePaths());
        $this->assertSame(2, $file->numberOfExecutedPaths());
    }

    public function testCountsClasses(): void
    {
        $file = $this->createFileNodeWithCoverageData();

        $this->assertSame(1, $file->numberOfClasses());
        $this->assertSame(1, $file->numberOfClasses());
    }

    public function testCountsTestedClasses(): void
    {
        $file = $this->createFileNodeWithCoverageData();

        $this->assertSame(1, $file->numberOfTestedClasses());
    }

    public function testCountsFunctions(): void
    {
        $file = $this->createFileNodeWithFunction();

        $this->assertSame(1, $file->numberOfFunctions());
    }

    public function testCountsMethods(): void
    {
        $file = $this->createFileNodeWithTestedClassAndTrait();

        $this->assertSame(2, $file->numberOfMethods());
    }

    public function testCountsTestedMethods(): void
    {
        $file = $this->createFileNodeWithTestedClassAndTrait();

        $this->assertSame(2, $file->numberOfTestedMethods());
    }

    private function createFileNodeWithTestedClassAndTrait(): File
    {
        $root = new Directory('root');

        $classMethod = new Method(
            'classMethod',
            1,
            5,
            'public function classMethod(): void',
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
            'MyTrait',
            'MyTrait',
            '',
            'test.php',
            6,
            10,
            [],
            ['traitMethod' => $traitMethod],
        );

        $lineCoverageData = array_fill(1, 10, ['test1']);

        return new File(
            'test.php',
            $root,
            'abc123',
            $lineCoverageData,
            [],
            ['test1'   => ['size' => 'small', 'status' => 'passed', 'time' => 0.0]],
            ['MyClass' => $class],
            ['MyTrait' => $trait],
            [],
            new LinesOfCode(10, 0, 10),
        );
    }

    private function createFileNodeWithCoverageData(): File
    {
        $root = new Directory('root');

        $method = new Method(
            'classMethod',
            1,
            5,
            'public function classMethod(): void',
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
            ['classMethod' => $method],
        );

        $function = new Function_(
            'myFunc',
            'myFunc',
            '',
            6,
            10,
            'function myFunc(): void',
            1,
        );

        $lineCoverageData = array_fill(1, 10, ['test1']);

        $functionCoverageData = [
            'MyClass->classMethod' => new ProcessedFunctionCoverageData(
                [new ProcessedBranchCoverageData(0, 5, 1, 5, ['test1'], [], [])],
                [new ProcessedPathCoverageData([0 => 0], ['test1'])],
            ),
            'myFunc' => new ProcessedFunctionCoverageData(
                [new ProcessedBranchCoverageData(0, 5, 6, 10, ['test1'], [], [])],
                [new ProcessedPathCoverageData([0 => 0], ['test1'])],
            ),
        ];

        return new File(
            'test.php',
            $root,
            'abc123',
            $lineCoverageData,
            $functionCoverageData,
            ['test1'   => ['size' => 'small', 'status' => 'passed', 'time' => 0.0]],
            ['MyClass' => $class],
            [],
            ['myFunc' => $function],
            new LinesOfCode(10, 0, 10),
            true,
        );
    }

    private function createFileNode(): File
    {
        $root = new Directory('root');

        return new File(
            'test.php',
            $root,
            'abc123',
            [],
            [],
            [],
            [],
            [],
            [],
            new LinesOfCode(0, 0, 0),
        );
    }

    private function createFileNodeWithTrait(): File
    {
        $root = new Directory('root');

        $method = new Method(
            'traitMethod',
            1,
            10,
            'public function traitMethod(): void',
            Visibility::Public,
            2,
        );

        $trait = new Trait_(
            'MyTrait',
            'MyTrait',
            '',
            'test.php',
            1,
            10,
            [],
            ['traitMethod' => $method],
        );

        return new File(
            'test.php',
            $root,
            'abc123',
            [1 => ['test1'], 2 => [], 3 => null],
            [],
            ['test1' => ['size' => 'small', 'status' => 'passed', 'time' => 0.0]],
            [],
            ['MyTrait' => $trait],
            [],
            new LinesOfCode(10, 0, 10),
        );
    }

    private function createFileNodeWithFunction(): File
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

        return new File(
            'test.php',
            $root,
            'abc123',
            [1 => ['test1'], 2 => []],
            [],
            ['test1' => ['size' => 'small', 'status' => 'passed', 'time' => 0.0]],
            [],
            [],
            ['myFunc' => $function],
            new LinesOfCode(5, 0, 5),
        );
    }
}
