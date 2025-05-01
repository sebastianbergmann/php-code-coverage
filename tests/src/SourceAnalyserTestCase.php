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
                38,
                39,
                40,
                41,
                42,
            ],
            $this->analyser()->analyse(
                TEST_FILES_PATH . 'source_with_ignore.php',
                file_get_contents(TEST_FILES_PATH . 'source_with_ignore.php'),
                true,
                true,
            )->ignoredLines(),
        );
    }

    public function testGetLinesToBeIgnored2(): void
    {
        $this->assertSame(
            [],
            $this->analyser()->analyse(
                TEST_FILES_PATH . 'source_without_ignore.php',
                file_get_contents(TEST_FILES_PATH . 'source_without_ignore.php'),
                true,
                true,
            )->ignoredLines(),
        );
    }

    public function testGetLinesToBeIgnored3(): void
    {
        $this->assertSame(
            [
                3,
            ],
            $this->analyser()->analyse(
                TEST_FILES_PATH . 'source_with_class_and_anonymous_function.php',
                file_get_contents(TEST_FILES_PATH . 'source_with_class_and_anonymous_function.php'),
                true,
                true,
            )->ignoredLines(),
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
            $this->analyser()->analyse(
                TEST_FILES_PATH . 'source_with_class_and_fqcn_constant.php',
                file_get_contents(TEST_FILES_PATH . 'source_with_class_and_fqcn_constant.php'),
                true,
                true,
            )->ignoredLines(),
        );
    }

    public function testGetLinesToBeIgnoredOneLineAnnotations(): void
    {
        $this->assertSame(
            [
                4,
                9,
                29,
                31,
                32,
                33,
            ],
            $this->analyser()->analyse(
                TEST_FILES_PATH . 'source_with_oneline_annotations.php',
                file_get_contents(TEST_FILES_PATH . 'source_with_oneline_annotations.php'),
                true,
                true,
            )->ignoredLines(),
        );
    }

    public function testGetLinesToBeIgnoredWhenIgnoreIsDisabled(): void
    {
        $this->assertSame(
            [
                11,
                18,
                33,
            ],
            $this->analyser()->analyse(
                TEST_FILES_PATH . 'source_with_ignore.php',
                file_get_contents(TEST_FILES_PATH . 'source_with_ignore.php'),
                false,
                false,
            )->ignoredLines(),
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
            $this->analyser()->analyse(
                TEST_FILES_PATH . 'source_with_ignore_attributes.php',
                file_get_contents(TEST_FILES_PATH . 'source_with_ignore_attributes.php'),
                true,
                true,
            )->ignoredLines(),
        );
    }

    #[Ticket('https://github.com/sebastianbergmann/php-code-coverage/issues/1033')]
    public function testEnumWithEnumLevelIgnore(): void
    {
        $this->assertSame(
            range(5, 13),
            $this->analyser()->analyse(
                TEST_FILES_PATH . 'source_with_enum_and_enum_level_ignore_annotation.php',
                file_get_contents(TEST_FILES_PATH . 'source_with_enum_and_enum_level_ignore_annotation.php'),
                true,
                true,
            )->ignoredLines(),
        );
    }

    #[Ticket('https://github.com/sebastianbergmann/php-code-coverage/issues/1033')]
    public function testEnumWithMethodLevelIgnore(): void
    {
        $this->assertSame(
            range(9, 12),
            $this->analyser()->analyse(
                TEST_FILES_PATH . 'source_with_enum_and_method_level_ignore_annotation.php',
                file_get_contents(TEST_FILES_PATH . 'source_with_enum_and_method_level_ignore_annotation.php'),
                true,
                true,
            )->ignoredLines(),
        );
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

    abstract protected function analyser(): SourceAnalyser;
}
