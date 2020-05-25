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

final class RawCodeCoverageDataTest extends TestCase
{
    /**
     * In the standard XDebug format, there is only line data. Therefore output should match input.
     */
    public function testLineDataFromStandardXDebugFormat(): void
    {
        $lineDataFromDriver = [
            '/some/path/SomeClass.php' => [
                8  => 1,
                9  => -2,
                13 => -1,
            ],
        ];

        $dataObject = RawCodeCoverageData::fromXdebugWithoutPathCoverage($lineDataFromDriver);

        $this->assertEquals($lineDataFromDriver, $dataObject->getLineCoverage());
    }

    /**
     * In the path-coverage XDebug format, the line data exists inside a "lines" array key where the file has
     * classes or functions. For files without them, the data is stored in the line-only format.
     */
    public function testLineDataFromPathCoverageXDebugFormat(): void
    {
        $rawDataFromDriver = [
            '/some/path/SomeClass.php' => [
                'lines' => [
                    8  => 1,
                    9  => -2,
                    13 => -1,
                ],
                'functions' => [

                ],
            ],
            '/some/path/justAScript.php' => [
                18  => 1,
                19  => -2,
                113 => -1,
            ],
        ];

        $lineData = [
            '/some/path/SomeClass.php' => [
                8  => 1,
                9  => -2,
                13 => -1,
            ],
            '/some/path/justAScript.php' => [
                18  => 1,
                19  => -2,
                113 => -1,
            ],
        ];

        $dataObject = RawCodeCoverageData::fromXdebugWithPathCoverage($rawDataFromDriver);

        $this->assertEquals($lineData, $dataObject->getLineCoverage());
    }

    public function testClear(): void
    {
        $lineDataFromDriver = [
            '/some/path/SomeClass.php' => [
                8  => 1,
                9  => -2,
                13 => -1,
            ],
        ];

        $dataObject = RawCodeCoverageData::fromXdebugWithoutPathCoverage($lineDataFromDriver);

        $dataObject->clear();

        $this->assertEmpty($dataObject->getLineCoverage());
    }

    public function testRemoveCoverageDataForFile(): void
    {
        $lineDataFromDriver = [
            '/some/path/SomeClass.php' => [
                8  => 1,
                9  => -2,
                13 => -1,
            ],
            '/some/path/SomeOtherClass.php' => [
                18  => 1,
                19  => -2,
                113 => -1,
            ],
            '/some/path/AnotherClass.php' => [
                28  => 1,
                29  => -2,
                213 => -1,
            ],
        ];

        $expectedFilterResult = [
            '/some/path/SomeClass.php' => [
                8  => 1,
                9  => -2,
                13 => -1,
            ],
            '/some/path/AnotherClass.php' => [
                28  => 1,
                29  => -2,
                213 => -1,
            ],
        ];

        $dataObject = RawCodeCoverageData::fromXdebugWithoutPathCoverage($lineDataFromDriver);

        $dataObject->removeCoverageDataForFile('/some/path/SomeOtherClass.php');

        $this->assertEquals($expectedFilterResult, $dataObject->getLineCoverage());
    }

    public function testKeepCoverageDataOnlyForLines(): void
    {
        $lineDataFromDriver = [
            '/some/path/SomeClass.php' => [
                8  => 1,
                9  => -2,
                13 => -1,
            ],
            '/some/path/SomeOtherClass.php' => [
                18  => 1,
                19  => -2,
                113 => -1,
            ],
            '/some/path/AnotherClass.php' => [
                28  => 1,
                29  => -2,
                213 => -1,
            ],
        ];

        $expectedFilterResult = [
            '/some/path/SomeClass.php' => [
                9  => -2,
                13 => -1,
            ],
            '/some/path/SomeOtherClass.php' => [
            ],
            '/some/path/AnotherClass.php' => [
                28  => 1,
            ],
        ];

        $dataObject = RawCodeCoverageData::fromXdebugWithoutPathCoverage($lineDataFromDriver);

        $dataObject->keepCoverageDataOnlyForLines('/some/path/SomeClass.php', [9, 13]);
        $dataObject->keepCoverageDataOnlyForLines('/some/path/SomeOtherClass.php', [999]);
        $dataObject->keepCoverageDataOnlyForLines('/some/path/AnotherClass.php', [28]);

        $this->assertEquals($expectedFilterResult, $dataObject->getLineCoverage());
    }

    public function testRemoveCoverageDataForLines(): void
    {
        $lineDataFromDriver = [
            '/some/path/SomeClass.php' => [
                8  => 1,
                9  => -2,
                13 => -1,
            ],
            '/some/path/SomeOtherClass.php' => [
                18  => 1,
                19  => -2,
                113 => -1,
            ],
            '/some/path/AnotherClass.php' => [
                28  => 1,
                29  => -2,
                213 => -1,
            ],
        ];

        $expectedFilterResult = [
            '/some/path/SomeClass.php' => [
                8  => 1,
            ],
            '/some/path/SomeOtherClass.php' => [
                18  => 1,
                19  => -2,
                113 => -1,
            ],
            '/some/path/AnotherClass.php' => [
                29  => -2,
                213 => -1,
            ],
        ];

        $dataObject = RawCodeCoverageData::fromXdebugWithoutPathCoverage($lineDataFromDriver);

        $dataObject->removeCoverageDataForLines('/some/path/SomeClass.php', [9, 13]);
        $dataObject->removeCoverageDataForLines('/some/path/SomeOtherClass.php', [999]);
        $dataObject->removeCoverageDataForLines('/some/path/AnotherClass.php', [28]);

        $this->assertEquals($expectedFilterResult, $dataObject->getLineCoverage());
    }

    public function testUseStatementsAreUncovered(): void
    {
        $file   = TEST_FILES_PATH . 'source_with_use_statements.php';
        $tokens = new \PHP_Token_Stream($file);

        $this->assertEquals(
            [
                11,
                12,
                14,
                15,
                17,
                18,
                19,
                20,
                21,
                22,
            ],
            \array_keys(RawCodeCoverageData::fromUncoveredFile($file, $tokens)->getLineCoverage()[$file])
        );
    }

    public function testEmptyClassesAreUncovered(): void
    {
        $file   = TEST_FILES_PATH . 'source_with_empty_class.php';
        $tokens = new \PHP_Token_Stream($file);

        $this->assertEquals(
            [
                12,
                14,
            ],
            \array_keys(RawCodeCoverageData::fromUncoveredFile($file, $tokens)->getLineCoverage()[$file])
        );
    }

    public function testInterfacesAreUncovered(): void
    {
        $file   = TEST_FILES_PATH . 'source_with_interface.php';
        $tokens = new \PHP_Token_Stream($file);

        $this->assertEquals(
            [
                6,
                7,
                9,
                10,
                12,
                13,
                14,
                15,
                16,
                17,
            ],
            \array_keys(RawCodeCoverageData::fromUncoveredFile($file, $tokens)->getLineCoverage()[$file])
        );
    }

    public function testInlineCommentsKeepTheLine(): void
    {
        $file   = TEST_FILES_PATH . 'source_with_oneline_annotations.php';
        $tokens = new \PHP_Token_Stream($file);

        $this->assertEquals(
            [
                12,
                13,
                17,
                19,
                22,
                26,
                29,
                31,
                32,
                33,
                35,
                36,
            ],
            \array_keys(RawCodeCoverageData::fromUncoveredFile($file, $tokens)->getLineCoverage()[$file])
        );
    }
}
