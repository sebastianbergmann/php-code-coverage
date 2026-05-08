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

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Small;
use PHPUnit\Framework\TestCase;
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

        $this->assertSame(1, $file->count());
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

        $traits    = $file->traits();
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

    public function testNumberOfExecutedLinesByTestSize(): void
    {
        $file = $this->createFileNodeWithMixedTestSizes();

        $this->assertSame(12, $file->numberOfExecutableLines());
        $this->assertSame(10, $file->numberOfExecutedLines());
        $this->assertSame(6, $file->numberOfExecutedLinesBySmallTests());
        $this->assertSame(4, $file->numberOfExecutedLinesByMediumTests());
        $this->assertSame(2, $file->numberOfExecutedLinesByLargeTests());
        $this->assertSame(8, $file->numberOfExecutedLinesBySmallOrMediumTests());
        $this->assertSame(8, $file->numberOfExecutedLinesBySmallOrLargeTests());
        $this->assertSame(6, $file->numberOfExecutedLinesByMediumOrLargeTests());
        $this->assertSame(10, $file->numberOfExecutedLinesBySmallOrMediumOrLargeTests());
    }

    public function testNumberOfTestedClassesByTestSize(): void
    {
        $file = $this->createFileNodeWithMixedTestSizes();

        $this->assertSame(3, $file->numberOfClasses());
        $this->assertSame(3, $file->numberOfTestedClasses());
        $this->assertSame(1, $file->numberOfTestedClassesBySmallTests());
        $this->assertSame(1, $file->numberOfTestedClassesByMediumTests());
        $this->assertSame(1, $file->numberOfTestedClassesByLargeTests());
        $this->assertSame(2, $file->numberOfTestedClassesBySmallOrMediumTests());
        $this->assertSame(2, $file->numberOfTestedClassesBySmallOrLargeTests());
        $this->assertSame(2, $file->numberOfTestedClassesByMediumOrLargeTests());
        $this->assertSame(3, $file->numberOfTestedClassesBySmallOrMediumOrLargeTests());
    }

    public function testNumberOfTestedTraitsByTestSize(): void
    {
        $file = $this->createFileNodeWithMixedTestSizes();

        $this->assertSame(1, $file->numberOfTraits());
        $this->assertSame(1, $file->numberOfTestedTraits());
        $this->assertSame(1, $file->numberOfTestedTraitsBySmallTests());
        $this->assertSame(1, $file->numberOfTestedTraitsByMediumTests());
        $this->assertSame(0, $file->numberOfTestedTraitsByLargeTests());
        $this->assertSame(1, $file->numberOfTestedTraitsBySmallOrMediumTests());
        $this->assertSame(1, $file->numberOfTestedTraitsBySmallOrLargeTests());
        $this->assertSame(1, $file->numberOfTestedTraitsByMediumOrLargeTests());
        $this->assertSame(1, $file->numberOfTestedTraitsBySmallOrMediumOrLargeTests());
    }

    public function testNumberOfTestedMethodsByTestSize(): void
    {
        $file = $this->createFileNodeWithMixedTestSizes();

        $this->assertSame(4, $file->numberOfMethods());
        $this->assertSame(4, $file->numberOfTestedMethods());
        $this->assertSame(2, $file->numberOfTestedMethodsBySmallTests());
        $this->assertSame(2, $file->numberOfTestedMethodsByMediumTests());
        $this->assertSame(1, $file->numberOfTestedMethodsByLargeTests());
        $this->assertSame(3, $file->numberOfTestedMethodsBySmallOrMediumTests());
        $this->assertSame(3, $file->numberOfTestedMethodsBySmallOrLargeTests());
        $this->assertSame(3, $file->numberOfTestedMethodsByMediumOrLargeTests());
        $this->assertSame(4, $file->numberOfTestedMethodsBySmallOrMediumOrLargeTests());
    }

    public function testNumberOfTestedFunctionsByTestSize(): void
    {
        $file = $this->createFileNodeWithMixedTestSizes();

        $this->assertSame(2, $file->numberOfFunctions());
        $this->assertSame(1, $file->numberOfTestedFunctions());
        $this->assertSame(1, $file->numberOfTestedFunctionsBySmallTests());
        $this->assertSame(0, $file->numberOfTestedFunctionsByMediumTests());
        $this->assertSame(0, $file->numberOfTestedFunctionsByLargeTests());
        $this->assertSame(1, $file->numberOfTestedFunctionsBySmallOrMediumTests());
        $this->assertSame(1, $file->numberOfTestedFunctionsBySmallOrLargeTests());
        $this->assertSame(0, $file->numberOfTestedFunctionsByMediumOrLargeTests());
        $this->assertSame(1, $file->numberOfTestedFunctionsBySmallOrMediumOrLargeTests());
    }

    public function testTestSizeCountersAreZeroWhenNoTestDataIsAvailable(): void
    {
        $file = $this->createFileNode();

        $this->assertSame(0, $file->numberOfExecutedLinesBySmallTests());
        $this->assertSame(0, $file->numberOfExecutedLinesByMediumTests());
        $this->assertSame(0, $file->numberOfExecutedLinesByLargeTests());
        $this->assertSame(0, $file->numberOfExecutedLinesBySmallOrMediumTests());
        $this->assertSame(0, $file->numberOfExecutedLinesBySmallOrLargeTests());
        $this->assertSame(0, $file->numberOfExecutedLinesByMediumOrLargeTests());
        $this->assertSame(0, $file->numberOfExecutedLinesBySmallOrMediumOrLargeTests());
        $this->assertSame(0, $file->numberOfTestedClassesBySmallTests());
        $this->assertSame(0, $file->numberOfTestedTraitsBySmallTests());
        $this->assertSame(0, $file->numberOfTestedMethodsBySmallTests());
        $this->assertSame(0, $file->numberOfTestedFunctionsBySmallTests());
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

    private function createFileNodeWithMixedTestSizes(): File
    {
        $root = new Directory('root');

        $smallMethod  = new Method('smallMethod', 1, 2, 'public function smallMethod(): void', Visibility::Public, 1);
        $mediumMethod = new Method('mediumMethod', 3, 4, 'public function mediumMethod(): void', Visibility::Public, 1);
        $largeMethod  = new Method('largeMethod', 5, 6, 'public function largeMethod(): void', Visibility::Public, 1);
        $mixedMethod  = new Method('mixedMethod', 7, 8, 'public function mixedMethod(): void', Visibility::Public, 1);

        $smallClass  = new Class_('SmallClass', 'SmallClass', '', 'test.php', 1, 2, null, [], [], ['smallMethod' => $smallMethod]);
        $mediumClass = new Class_('MediumClass', 'MediumClass', '', 'test.php', 3, 4, null, [], [], ['mediumMethod' => $mediumMethod]);
        $largeClass  = new Class_('LargeClass', 'LargeClass', '', 'test.php', 5, 6, null, [], [], ['largeMethod' => $largeMethod]);
        $mixedTrait  = new Trait_('MixedTrait', 'MixedTrait', '', 'test.php', 7, 8, [], ['mixedMethod' => $mixedMethod]);

        $smallFunc    = new Function_('smallFunc', 'smallFunc', '', 9, 10, 'function smallFunc(): void', 1);
        $untestedFunc = new Function_('untestedFunc', 'untestedFunc', '', 11, 12, 'function untestedFunc(): void', 1);

        $lineCoverageData = [
            1  => ['tSmall'],
            2  => ['tSmall'],
            3  => ['tMedium'],
            4  => ['tMedium'],
            5  => ['tLarge'],
            6  => ['tLarge'],
            7  => ['tSmall', 'tMedium'],
            8  => ['tSmall', 'tMedium'],
            9  => ['tSmall'],
            10 => ['tSmall'],
            11 => [],
            12 => [],
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
            [
                'SmallClass'  => $smallClass,
                'MediumClass' => $mediumClass,
                'LargeClass'  => $largeClass,
            ],
            ['MixedTrait' => $mixedTrait],
            [
                'smallFunc'    => $smallFunc,
                'untestedFunc' => $untestedFunc,
            ],
            new LinesOfCode(12, 0, 12),
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
