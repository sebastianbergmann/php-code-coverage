<?php
/*
 * This file is part of the php-code-coverage package.
 *
 * (c) Sebastian Bergmann <sebastian@phpunit.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SebastianBergmann\CodeCoverage;

/**
 * @covers SebastianBergmann\CodeCoverage\LineFilter
 */
class LineFilterTest extends TestCase
{
    /**
     * @var LineFilter
     */
    private $lineFilter;

    protected function setUp()
    {
        $this->lineFilter = new LineFilter();
    }

    public function testSetCacheTokens()
    {
        $this->lineFilter->setCacheTokens(true);
        self::assertSame(true, $this->lineFilter->getCacheTokens());
        $this->lineFilter->setCacheTokens(false);
        self::assertSame(false, $this->lineFilter->getCacheTokens());
    }

    public function testSetIgnoreDeprecatedCode()
    {
        $this->lineFilter->setIgnoreDeprecatedCode(true);
        self::assertSame(true, $this->lineFilter->getIgnoreDeprecatedCode());
        $this->lineFilter->setIgnoreDeprecatedCode(false);
        self::assertSame(false, $this->lineFilter->getIgnoreDeprecatedCode());
    }

    public function testSetDisableIgnoredLines()
    {
        $this->lineFilter->setDisableIgnoredLines(true);
        self::assertSame(true, $this->lineFilter->getDisableIgnoredLines());
        $this->lineFilter->setDisableIgnoredLines(false);
        self::assertSame(false, $this->lineFilter->getDisableIgnoredLines());
    }

    public function testGetLinesToBeIgnored()
    {
        $allLines = range(1, 38);
        $executableLines = [2, 6, 29, 31];
        $linesToBeIgnored = \array_values(\array_diff($allLines, $executableLines));

        $actualLinesToBeIgnored = $this->lineFilter->getLinesToBeIgnored(
            TEST_FILES_PATH . 'source_with_ignore.php'
        );

        self::assertSame($linesToBeIgnored, $actualLinesToBeIgnored);
    }

    public function testGetLinesToBeIgnored2()
    {
        $actualLinesToBeIgnored = $this->lineFilter->getLinesToBeIgnored(
            TEST_FILES_PATH . 'source_without_ignore.php'
        );

        self::assertSame([1, 5], $actualLinesToBeIgnored);
    }

    public function testGetLinesToBeIgnored3()
    {
        $allLines = range(1, 20);
        $executableLines = [6, 7, 9, 10, 12, 13, 14, 17, 18];
        $linesToBeIgnored = \array_values(\array_diff($allLines, $executableLines));

        $actualLinesToBeIgnored = $this->lineFilter->getLinesToBeIgnored(
            TEST_FILES_PATH . 'source_with_class_and_anonymous_function.php'
        );

        self::assertSame($linesToBeIgnored, $actualLinesToBeIgnored);
    }

    public function testGetLinesToBeIgnoredOneLineAnnotations()
    {
        $allLines = range(1, 37);
        $executableLines = [12, 13, 17, 19, 22, 26, 35, 36];
        $linesToBeIgnored = \array_values(\array_diff($allLines, $executableLines));

        $actualLinesToBeIgnored = $this->lineFilter->getLinesToBeIgnored(
            TEST_FILES_PATH . 'source_with_oneline_annotations.php'
        );

        self::assertSame($linesToBeIgnored, $actualLinesToBeIgnored);
    }

    public function testGetLinesToBeIgnoredWhenIgnoreIsDisabled()
    {
        $this->lineFilter->setDisableIgnoredLines(true);

        $allLines = range(1, 38);
        $executableLines = [1,2,3,4,5,6,8,9,10,14,15,24,25,28,29,30,31,38];
        $linesToBeIgnored = \array_values(\array_diff($allLines, $executableLines));

        $actualLinesToBeIgnored = $this->lineFilter->getLinesToBeIgnored(
            TEST_FILES_PATH . 'source_with_ignore.php'
        );

        self::assertSame($linesToBeIgnored, $actualLinesToBeIgnored);
    }
}
