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

use PHPUnit\Framework\TestCase;

/**
 * @covers \SebastianBergmann\CodeCoverage\StaticAnalysis\ParsingCoveredFileAnalyser
 * @covers \SebastianBergmann\CodeCoverage\StaticAnalysis\CodeUnitFindingVisitor
 * @covers \SebastianBergmann\CodeCoverage\StaticAnalysis\IgnoredLinesFindingVisitor
 */
final class CoveredFileAnalyserTest extends TestCase
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
            ],
            (new ParsingCoveredFileAnalyser(true, true))->ignoredLinesFor(
                TEST_FILES_PATH . 'source_with_ignore.php'
            )
        );
    }

    public function testGetLinesToBeIgnored2(): void
    {
        $this->assertEquals(
            [],
            (new ParsingCoveredFileAnalyser(true, true))->ignoredLinesFor(
                TEST_FILES_PATH . 'source_without_ignore.php'
            )
        );
    }

    public function testGetLinesToBeIgnored3(): void
    {
        $this->assertEquals(
            [
                3,
            ],
            (new ParsingCoveredFileAnalyser(true, true))->ignoredLinesFor(
                TEST_FILES_PATH . 'source_with_class_and_anonymous_function.php'
            )
        );
    }

    /**
     * @ticket https://github.com/sebastianbergmann/php-code-coverage/issues/793
     * @ticket https://github.com/sebastianbergmann/php-code-coverage/issues/794
     */
    public function testLineWithFullyQualifiedClassNameConstantIsNotIgnored(): void
    {
        $this->assertSame(
            [
                2,
            ],
            (new ParsingCoveredFileAnalyser(true, true))->ignoredLinesFor(
                TEST_FILES_PATH . 'source_with_class_and_fqcn_constant.php'
            )
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
            (new ParsingCoveredFileAnalyser(true, true))->ignoredLinesFor(
                TEST_FILES_PATH . 'source_with_oneline_annotations.php'
            )
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
            (new ParsingCoveredFileAnalyser(false, false))->ignoredLinesFor(
                TEST_FILES_PATH . 'source_with_ignore.php'
            )
        );
    }
}
