<?php declare(strict_types=1);
/*
 * This file is part of phpunit/php-code-coverage.
 *
 * (c) Sebastian Bergmann <sebastian@phpunit.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace SebastianBergmann\CodeCoverage;

/**
 * @covers \SebastianBergmann\CodeCoverage\IgnoredLinesFinder
 */
final class IgnoredLinesFinderTest extends TestCase
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
            (new IgnoredLinesFinder)->findIgnoredLinesInFile(
                TEST_FILES_PATH . 'source_with_ignore.php',
                true,
                true,
            )
        );
    }

    public function testGetLinesToBeIgnored2(): void
    {
        $this->assertEquals(
            [],
            (new IgnoredLinesFinder)->findIgnoredLinesInFile(
                TEST_FILES_PATH . 'source_without_ignore.php',
                true,
                true,
            )
        );
    }

    public function testGetLinesToBeIgnored3(): void
    {
        $this->assertEquals(
            [
                3,
            ],
            (new IgnoredLinesFinder)->findIgnoredLinesInFile(
                TEST_FILES_PATH . 'source_with_class_and_anonymous_function.php',
                true,
                true,
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
            (new IgnoredLinesFinder)->findIgnoredLinesInFile(
                TEST_FILES_PATH . 'source_with_oneline_annotations.php',
                true,
                true,
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
            (new IgnoredLinesFinder)->findIgnoredLinesInFile(
                TEST_FILES_PATH . 'source_with_ignore.php',
                false,
                false,
            )
        );
    }
}
