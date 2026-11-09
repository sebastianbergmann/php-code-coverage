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

use function array_intersect_key;
use function file_get_contents;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Medium;
use PHPUnit\Framework\Attributes\RequiresPhp;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(BytecodeSourceAnalyser::class)]
#[UsesClass(BytecodeDumpParser::class)]
#[UsesClass(BytecodeLines::class)]
#[UsesClass(ParsingSourceAnalyser::class)]
#[UsesClass(CodeUnitFindingVisitor::class)]
#[UsesClass(IgnoredLinesFindingVisitor::class)]
#[UsesClass(ExecutableLinesFindingVisitor::class)]
#[UsesClass(DeadCodeFindingVisitor::class)]
#[Medium]
#[Group('static-analysis')]
final class BytecodeSourceAnalyserTest extends TestCase
{
    #[RequiresPhp('>= 8.6.0')]
    public function testDeterminesExecutableLinesFromBytecode(): void
    {
        $result = $this->analyse(
            new BytecodeSourceAnalyser(new ParsingSourceAnalyser, new BytecodeLines),
            TEST_FILES_PATH . 'source_for_bytecode_analysis.php',
        );

        $this->assertSame(
            [18 => 18, 24 => 24, 26 => 26, 31 => 31, 33 => 33, 37 => 37, 41 => 41],
            $result->executableLines(),
        );

        $this->assertSame([], $result->branchOperatorLines());
    }

    #[RequiresPhp('>= 8.6.0')]
    public function testDeterminesEverythingElseFromSyntax(): void
    {
        $file   = TEST_FILES_PATH . 'source_for_bytecode_analysis.php';
        $syntax = $this->analyse(new ParsingSourceAnalyser, $file);
        $result = $this->analyse(new BytecodeSourceAnalyser(new ParsingSourceAnalyser, new BytecodeLines), $file);

        $this->assertEquals($syntax->interfaces(), $result->interfaces());
        $this->assertEquals($syntax->classes(), $result->classes());
        $this->assertEquals($syntax->traits(), $result->traits());
        $this->assertEquals($syntax->functions(), $result->functions());
        $this->assertEquals($syntax->linesOfCode(), $result->linesOfCode());
        $this->assertSame($syntax->ignoredLines(), $result->ignoredLines());
        $this->assertTrue($result->wasParsed());
    }

    #[RequiresPhp('>= 8.6.0')]
    public function testOnlyCountsDeadLinesThatAreExecutable(): void
    {
        $file   = TEST_FILES_PATH . 'source_with_dead_code.php';
        $syntax = $this->analyse(new ParsingSourceAnalyser, $file);
        $result = $this->analyse(new BytecodeSourceAnalyser(new ParsingSourceAnalyser, new BytecodeLines), $file);

        $this->assertNotSame([], $result->deadLines());
        $this->assertSame(
            array_intersect_key($syntax->deadLines(), $result->executableLines()),
            $result->deadLines(),
        );
    }

    public function testUsesAnalysisOfSyntaxForFileThatCannotBeCompiled(): void
    {
        $file = TEST_FILES_PATH . 'source_for_bytecode_analysis.php';

        $this->assertEquals(
            $this->analyse(new ParsingSourceAnalyser, $file),
            $this->analyse(
                new BytecodeSourceAnalyser(
                    new ParsingSourceAnalyser,
                    new BytecodeLines(TEST_FILES_PATH . 'does_not_exist'),
                ),
                $file,
            ),
        );
    }

    public function testUsesAnalysisOfSyntaxForFileThatCannotBeParsed(): void
    {
        $file = TEST_FILES_PATH . 'source_that_cannot_be_parsed.php';

        $this->assertEquals(
            $this->analyse(new ParsingSourceAnalyser, $file),
            $this->analyse(new BytecodeSourceAnalyser(new ParsingSourceAnalyser, new BytecodeLines), $file),
        );
    }

    /**
     * @param non-empty-string $file
     */
    private function analyse(SourceAnalyser $analyser, string $file): AnalysisResult
    {
        $source = file_get_contents($file);

        if ($source === false) {
            $this->fail('Could not read ' . $file);
        }

        return $analyser->analyse($file, $source, true, false);
    }
}
