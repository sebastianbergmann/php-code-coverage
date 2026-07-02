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

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Small;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(AnalysisResult::class)]
#[UsesClass(Interface_::class)]
#[UsesClass(Class_::class)]
#[UsesClass(Trait_::class)]
#[UsesClass(Function_::class)]
#[UsesClass(LinesOfCode::class)]
#[Small]
#[Group('static-analysis')]
final class AnalysisResultTest extends TestCase
{
    public function testHasInterfaces(): void
    {
        $interface = new Interface_(
            'AnInterface',
            'example\AnInterface',
            'example',
            1,
            2,
            [],
        );

        $this->assertSame(
            ['example\AnInterface' => $interface],
            $this->analysisResult(interfaces: ['example\AnInterface' => $interface])->interfaces(),
        );
    }

    public function testHasClasses(): void
    {
        $class = new Class_(
            'AClass',
            'example\AClass',
            'example',
            'file.php',
            1,
            2,
            null,
            [],
            [],
            [],
        );

        $this->assertSame(
            ['example\AClass' => $class],
            $this->analysisResult(classes: ['example\AClass' => $class])->classes(),
        );
    }

    public function testHasTraits(): void
    {
        $trait = new Trait_(
            'ATrait',
            'example\ATrait',
            'example',
            'file.php',
            1,
            2,
            [],
            [],
        );

        $this->assertSame(
            ['example\ATrait' => $trait],
            $this->analysisResult(traits: ['example\ATrait' => $trait])->traits(),
        );
    }

    public function testHasFunctions(): void
    {
        $function = new Function_(
            'aFunction',
            'example\aFunction',
            'example',
            1,
            2,
            'aFunction(): void',
            1,
        );

        $this->assertSame(
            ['example\aFunction' => $function],
            $this->analysisResult(functions: ['example\aFunction' => $function])->functions(),
        );
    }

    public function testHasLinesOfCode(): void
    {
        $linesOfCode = new LinesOfCode(3, 1, 2);

        $this->assertSame($linesOfCode, $this->analysisResult(linesOfCode: $linesOfCode)->linesOfCode());
    }

    public function testHasExecutableLines(): void
    {
        $executableLines = [5 => 5, 6 => 6];

        $this->assertSame($executableLines, $this->analysisResult(executableLines: $executableLines)->executableLines());
    }

    public function testHasBranchOperatorLines(): void
    {
        $branchOperatorLines = [7 => true];

        $this->assertSame($branchOperatorLines, $this->analysisResult(branchOperatorLines: $branchOperatorLines)->branchOperatorLines());
    }

    public function testHasIgnoredLines(): void
    {
        $ignoredLines = [8 => 8];

        $this->assertSame($ignoredLines, $this->analysisResult(ignoredLines: $ignoredLines)->ignoredLines());
    }

    public function testHasDeadLines(): void
    {
        $deadLines = [9 => true];

        $this->assertSame($deadLines, $this->analysisResult(deadLines: $deadLines)->deadLines());
    }

    public function testHasNoDeadLinesByDefault(): void
    {
        $this->assertSame([], $this->analysisResult()->deadLines());
    }

    /**
     * @param array<string, Interface_> $interfaces
     * @param array<string, Class_>     $classes
     * @param array<string, Trait_>     $traits
     * @param array<string, Function_>  $functions
     * @param array<positive-int, int>  $executableLines
     * @param array<positive-int, true> $branchOperatorLines
     * @param array<positive-int, true> $deadLines
     * @param array<int, int>           $ignoredLines
     */
    private function analysisResult(array $interfaces = [], array $classes = [], array $traits = [], array $functions = [], ?LinesOfCode $linesOfCode = null, array $executableLines = [], array $branchOperatorLines = [], array $deadLines = [], array $ignoredLines = []): AnalysisResult
    {
        return new AnalysisResult(
            $interfaces,
            $classes,
            $traits,
            $functions,
            $linesOfCode ?? new LinesOfCode(0, 0, 0),
            $executableLines,
            $branchOperatorLines,
            $deadLines,
            $ignoredLines,
        );
    }
}
