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
