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

use function array_keys;
use function file_get_contents;
use function range;
use PHPUnit\Framework\Attributes\Ticket;
use PHPUnit\Framework\TestCase;

abstract class SourceAnalyserTestCase extends TestCase
{
    public function testGetLinesToBeIgnored(): void
    {
        $this->assertSame(
            [
                3,
                4,
                5,
                11,
                12,
                13,
                14,
                15,
                16,
                18,
                23,
                24,
                25,
                30,
                33,
                34,
                35,
                36,
                38,
                39,
                40,
                41,
                42,
            ],
            array_keys($this->analyser()->analyse(
                TEST_FILES_PATH . 'source_with_ignore.php',
                file_get_contents(TEST_FILES_PATH . 'source_with_ignore.php'),
                true,
                true,
            )->ignoredLines()),
        );
    }

    public function testGetLinesToBeIgnored2(): void
    {
        $this->assertSame(
            [],
            array_keys($this->analyser()->analyse(
                TEST_FILES_PATH . 'source_without_ignore.php',
                file_get_contents(TEST_FILES_PATH . 'source_without_ignore.php'),
                true,
                true,
            )->ignoredLines()),
        );
    }

    public function testGetLinesToBeIgnored3(): void
    {
        $this->assertSame(
            [
                3,
            ],
            array_keys($this->analyser()->analyse(
                TEST_FILES_PATH . 'source_with_class_and_anonymous_function.php',
                file_get_contents(TEST_FILES_PATH . 'source_with_class_and_anonymous_function.php'),
                true,
                true,
            )->ignoredLines()),
        );
    }

    #[Ticket('https://github.com/sebastianbergmann/php-code-coverage/issues/793')]
    #[Ticket('https://github.com/sebastianbergmann/php-code-coverage/issues/794')]
    public function testLineWithFullyQualifiedClassNameConstantIsNotIgnored(): void
    {
        $this->assertSame(
            [
                2,
            ],
            array_keys($this->analyser()->analyse(
                TEST_FILES_PATH . 'source_with_class_and_fqcn_constant.php',
                file_get_contents(TEST_FILES_PATH . 'source_with_class_and_fqcn_constant.php'),
                true,
                true,
            )->ignoredLines()),
        );
    }

    public function testGetLinesToBeIgnoredOneLineAnnotations(): void
    {
        $this->assertSame(
            [
                4,
                5,
                6,
                7,
                9,
                29,
                31,
                32,
                33,
            ],
            array_keys($this->analyser()->analyse(
                TEST_FILES_PATH . 'source_with_oneline_annotations.php',
                file_get_contents(TEST_FILES_PATH . 'source_with_oneline_annotations.php'),
                true,
                true,
            )->ignoredLines()),
        );
    }

    public function testGetLinesToBeIgnoredWhenIgnoreIsDisabled(): void
    {
        $this->assertSame(
            [
                11,
                18,
                33,
                34,
                35,
                36,
            ],
            array_keys($this->analyser()->analyse(
                TEST_FILES_PATH . 'source_with_ignore.php',
                file_get_contents(TEST_FILES_PATH . 'source_with_ignore.php'),
                false,
                false,
            )->ignoredLines()),
        );
    }

    public function testGetLinesOfCodeForFileWithoutNewline(): void
    {
        $this->assertSame(
            1,
            $this->analyser()->analyse(
                TEST_FILES_PATH . 'source_without_newline.php',
                file_get_contents(TEST_FILES_PATH . 'source_without_newline.php'),
                false,
                false,
            )->linesOfCode()->linesOfCode(),
        );
    }

    #[Ticket('https://github.com/sebastianbergmann/php-code-coverage/issues/885')]
    public function testGetLinesOfCodeForFileCrLineEndings(): void
    {
        $result = $this->analyser()->analyse(
            TEST_FILES_PATH . 'source_without_lf_only_cr.php',
            file_get_contents(TEST_FILES_PATH . 'source_without_lf_only_cr.php'),
            false,
            false,
        )->linesOfCode();

        $this->assertSame(4, $result->linesOfCode());
        $this->assertSame(2, $result->commentLinesOfCode());
        $this->assertSame(2, $result->nonCommentLinesOfCode());
    }

    public function testLinesCanBeIgnoredUsingAttribute(): void
    {
        $this->assertSame(
            [
                4,
                5,
                6,
                7,
                8,
                9,
                10,
                11,
                13,
                15,
                16,
                17,
                18,
                19,
            ],
            array_keys($this->analyser()->analyse(
                TEST_FILES_PATH . 'source_with_ignore_attributes.php',
                file_get_contents(TEST_FILES_PATH . 'source_with_ignore_attributes.php'),
                true,
                true,
            )->ignoredLines()),
        );
    }

    #[Ticket('https://github.com/sebastianbergmann/php-code-coverage/issues/1033')]
    public function testEnumWithEnumLevelIgnore(): void
    {
        $this->assertSame(
            range(5, 13),
            array_keys($this->analyser()->analyse(
                TEST_FILES_PATH . 'source_with_enum_and_enum_level_ignore_annotation.php',
                file_get_contents(TEST_FILES_PATH . 'source_with_enum_and_enum_level_ignore_annotation.php'),
                true,
                true,
            )->ignoredLines()),
        );
    }

    #[Ticket('https://github.com/sebastianbergmann/php-code-coverage/issues/1033')]
    public function testEnumWithMethodLevelIgnore(): void
    {
        $this->assertSame(
            range(9, 12),
            array_keys($this->analyser()->analyse(
                TEST_FILES_PATH . 'source_with_enum_and_method_level_ignore_annotation.php',
                file_get_contents(TEST_FILES_PATH . 'source_with_enum_and_method_level_ignore_annotation.php'),
                true,
                true,
            )->ignoredLines()),
        );
    }

    public function testIgnoreEndWithoutStartIsHandledGracefully(): void
    {
        $ignoredLines = array_keys($this->analyser()->analyse(
            TEST_FILES_PATH . 'source_with_codeCoverageIgnoreEnd_without_start.php',
            file_get_contents(TEST_FILES_PATH . 'source_with_codeCoverageIgnoreEnd_without_start.php'),
            true,
            true,
        )->ignoredLines());

        // Line 5 has the @codeCoverageIgnoreEnd without a prior start,
        // so it should still be included in ignored lines (start defaults to the token line)
        $this->assertContains(5, $ignoredLines);
    }

    public function testDeadCodeDetectionReportsUnreachableLines(): void
    {
        $result = $this->analyser()->analyse(
            TEST_FILES_PATH . 'source_with_dead_code.php',
            file_get_contents(TEST_FILES_PATH . 'source_with_dead_code.php'),
            false,
            false,
        );

        $deadLines       = $result->deadLines();
        $executableLines = $result->executableLines();

        foreach ([9, 10, 16, 22, 28, 35, 43, 50, 51, 60, 68, 69, 80, 89, 96, 103, 158] as $line) {
            $this->assertArrayHasKey($line, $deadLines, "Line {$line} should be reported as dead");
            $this->assertArrayHasKey($line, $executableLines, 'Dead lines must be a subset of executable lines');
        }
    }

    public function testDeadCodeDetectionLeavesLiveLinesAlone(): void
    {
        $result = $this->analyser()->analyse(
            TEST_FILES_PATH . 'source_with_dead_code.php',
            file_get_contents(TEST_FILES_PATH . 'source_with_dead_code.php'),
            false,
            false,
        );

        $deadLines = $result->deadLines();

        foreach ([8, 15, 21, 27, 34, 42, 49, 58, 67, 72, 78, 83, 88, 95, 104, 110, 113, 151, 162, 174, 191] as $line) {
            $this->assertArrayNotHasKey($line, $deadLines, "Line {$line} should be live");
        }
    }

    public function testCodeUnitsAreFound(): void
    {
        $analyser = new ParsingSourceAnalyser;

        $analysisResult = $analyser->analyse(
            __DIR__ . '/../_files/source_with_interfaces_classes_traits_functions.php',
            file_get_contents(__DIR__ . '/../_files/source_with_interfaces_classes_traits_functions.php'),
            true,
            true,
        );

        $this->assertCount(3, $analysisResult->interfaces());
        $this->assertArrayHasKey('SebastianBergmann\CodeCoverage\StaticAnalysis\A', $analysisResult->interfaces());
        $this->assertArrayHasKey('SebastianBergmann\CodeCoverage\StaticAnalysis\B', $analysisResult->interfaces());
        $this->assertArrayHasKey('SebastianBergmann\CodeCoverage\StaticAnalysis\C', $analysisResult->interfaces());

        $this->assertCount(2, $analysisResult->classes());
        $this->assertArrayHasKey('SebastianBergmann\CodeCoverage\StaticAnalysis\ParentClass', $analysisResult->classes());
        $this->assertArrayHasKey('SebastianBergmann\CodeCoverage\StaticAnalysis\ChildClass', $analysisResult->classes());

        $this->assertCount(1, $analysisResult->traits());
        $this->assertArrayHasKey('SebastianBergmann\CodeCoverage\StaticAnalysis\T', $analysisResult->traits());

        $this->assertCount(1, $analysisResult->functions());
        $this->assertArrayHasKey('SebastianBergmann\CodeCoverage\StaticAnalysis\f', $analysisResult->functions());
    }

    public function testFileThatCannotBeParsedHasDegradedAnalysisResult(): void
    {
        $analysisResult = $this->analyser()->analyse(
            TEST_FILES_PATH . 'source_that_cannot_be_parsed.php',
            file_get_contents(TEST_FILES_PATH . 'source_that_cannot_be_parsed.php'),
            true,
            true,
        );

        $this->assertFalse($analysisResult->wasParsed());
        $this->assertStringContainsString('Syntax error', $analysisResult->parseError());

        $this->assertSame([], $analysisResult->interfaces());
        $this->assertSame([], $analysisResult->classes());
        $this->assertSame([], $analysisResult->traits());
        $this->assertSame([], $analysisResult->functions());
        $this->assertSame([], $analysisResult->executableLines());
        $this->assertSame([], $analysisResult->branchOperatorLines());
        $this->assertSame([], $analysisResult->deadLines());

        $this->assertSame(6, $analysisResult->linesOfCode()->linesOfCode());
        $this->assertSame(0, $analysisResult->linesOfCode()->commentLinesOfCode());
        $this->assertSame(6, $analysisResult->linesOfCode()->nonCommentLinesOfCode());

        $this->assertSame([2, 3, 4], array_keys($analysisResult->ignoredLines()));
    }

    public function testFileThatCannotBeParsedHasNoIgnoredLinesWhenAnnotationsAreDisabled(): void
    {
        $analysisResult = $this->analyser()->analyse(
            TEST_FILES_PATH . 'source_that_cannot_be_parsed.php',
            file_get_contents(TEST_FILES_PATH . 'source_that_cannot_be_parsed.php'),
            false,
            false,
        );

        $this->assertFalse($analysisResult->wasParsed());
        $this->assertSame([], array_keys($analysisResult->ignoredLines()));
    }

    abstract protected function analyser(): SourceAnalyser;
}
