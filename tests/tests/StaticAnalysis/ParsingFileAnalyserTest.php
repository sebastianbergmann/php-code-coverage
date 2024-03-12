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

use function range;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Ticket;
use PHPUnit\Framework\TestCase;

#[CoversClass(CodeUnitFindingVisitor::class)]
#[CoversClass(IgnoredLinesFindingVisitor::class)]
#[CoversClass(ParsingFileAnalyser::class)]
final class ParsingFileAnalyserTest extends TestCase
{
    public function testGetLinesToBeIgnored(): void
    {
        $this->assertEquals(
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
            (new ParsingFileAnalyser(true, true))->ignoredLinesFor(
                TEST_FILES_PATH . 'source_with_ignore.php',
            ),
        );
    }

    public function testGetLinesToBeIgnored2(): void
    {
        $this->assertEquals(
            [],
            (new ParsingFileAnalyser(true, true))->ignoredLinesFor(
                TEST_FILES_PATH . 'source_without_ignore.php',
            ),
        );
    }

    public function testGetLinesToBeIgnored3(): void
    {
        $this->assertEquals(
            [
                3,
            ],
            (new ParsingFileAnalyser(true, true))->ignoredLinesFor(
                TEST_FILES_PATH . 'source_with_class_and_anonymous_function.php',
            ),
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
            (new ParsingFileAnalyser(true, true))->ignoredLinesFor(
                TEST_FILES_PATH . 'source_with_class_and_fqcn_constant.php',
            ),
        );
    }

    public function testGetLinesToBeIgnoredOneLineAnnotations(): void
    {
        $this->assertEquals(
            [
                4,
                9,
                29,
                31,
                32,
                33,
            ],
            (new ParsingFileAnalyser(true, true))->ignoredLinesFor(
                TEST_FILES_PATH . 'source_with_oneline_annotations.php',
            ),
        );
    }

    public function testGetLinesToBeIgnoredWhenIgnoreIsDisabled(): void
    {
        $this->assertEquals(
            [
                11,
                18,
                33,
            ],
            (new ParsingFileAnalyser(false, false))->ignoredLinesFor(
                TEST_FILES_PATH . 'source_with_ignore.php',
            ),
        );
    }

    public function testGetLinesOfCodeForFileWithoutNewline(): void
    {
        $this->assertSame(
            1,
            (new ParsingFileAnalyser(false, false))->linesOfCodeFor(
                TEST_FILES_PATH . 'source_without_newline.php',
            )['linesOfCode'],
        );
    }

    #[Ticket('https://github.com/sebastianbergmann/php-code-coverage/issues/885')]
    public function testGetLinesOfCodeForFileCrLineEndings(): void
    {
        $result = (new ParsingFileAnalyser(false, false))->linesOfCodeFor(
            TEST_FILES_PATH . 'source_without_lf_only_cr.php',
        );

        $this->assertSame(4, $result['linesOfCode']);
        $this->assertSame(2, $result['commentLinesOfCode']);
        $this->assertSame(2, $result['nonCommentLinesOfCode']);
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
            (new ParsingFileAnalyser(true, true))->ignoredLinesFor(
                TEST_FILES_PATH . 'source_with_ignore_attributes.php',
            ),
        );
    }

    #[Ticket('https://github.com/sebastianbergmann/php-code-coverage/issues/1033')]
    public function testEnumWithEnumLevelIgnore(): void
    {
        $this->assertSame(
            range(5, 13),
            (new ParsingFileAnalyser(true, true))->ignoredLinesFor(
                TEST_FILES_PATH . 'source_with_enum_and_enum_level_ignore_annotation.php',
            ),
        );
    }

    #[Ticket('https://github.com/sebastianbergmann/php-code-coverage/issues/1033')]
    public function testEnumWithMethodLevelIgnore(): void
    {
        $this->assertSame(
            range(9, 12),
            (new ParsingFileAnalyser(true, true))->ignoredLinesFor(
                TEST_FILES_PATH . 'source_with_enum_and_method_level_ignore_annotation.php',
            ),
        );
    }
}
