<?php declare(strict_types=1);
/*
 * This file is part of phpunit/php-code-coverage.
 *
 * (c) Sebastian Bergmann <sebastian@phpunit.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace SebastianBergmann\CodeCoverage\Data;

use function array_keys;
use SebastianBergmann\CodeCoverage\RawCodeCoverageData;
use SebastianBergmann\CodeCoverage\StaticAnalysis\ParsingFileAnalyser;
use SebastianBergmann\CodeCoverage\TestCase;

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

        $this->assertEquals($lineDataFromDriver, $dataObject->lineCoverage());
    }

    /**
     * In the path-coverage XDebug format, the line data exists inside a "lines" array key.
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
                'lines' => [
                    18  => 1,
                    19  => -2,
                    113 => -1,
                ],
                'functions' => [

                ],
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

        $this->assertEquals($lineData, $dataObject->lineCoverage());
    }

    /**
     * In the path-coverage XDebug format for Xdebug < 2.9.6, the line data exists inside a "lines" array key where the
     * file has classes or functions. For files without them, the data is stored in the line-only format.
     */
    public function testLineDataFromMixedCoverageXDebugFormat(): void
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

        $dataObject = RawCodeCoverageData::fromXdebugWithMixedCoverage($rawDataFromDriver);

        $this->assertEquals($lineData, $dataObject->lineCoverage());
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

        $this->assertEmpty($dataObject->lineCoverage());
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

        $this->assertEquals($expectedFilterResult, $dataObject->lineCoverage());
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
                28 => 1,
            ],
        ];

        $dataObject = RawCodeCoverageData::fromXdebugWithoutPathCoverage($lineDataFromDriver);

        $dataObject->keepLineCoverageDataOnlyForLines('/some/path/SomeClass.php', [9, 13]);
        $dataObject->keepLineCoverageDataOnlyForLines('/some/path/SomeOtherClass.php', [999]);
        $dataObject->keepLineCoverageDataOnlyForLines('/some/path/AnotherClass.php', [28]);

        $this->assertEquals($expectedFilterResult, $dataObject->lineCoverage());
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
                8 => 1,
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

        $this->assertEquals($expectedFilterResult, $dataObject->lineCoverage());
    }

    public function testUseStatementsAreUncovered(): void
    {
        $file = TEST_FILES_PATH . 'source_with_use_statements.php';

        $this->assertEquals(
            [
                12,
                14,
                16,
                18,
            ],
            array_keys(RawCodeCoverageData::fromUncoveredFile($file, new ParsingFileAnalyser(true, true))->lineCoverage()[$file])
        );
    }

    public function testEmptyClassesAreUncovered(): void
    {
        $file = TEST_FILES_PATH . 'source_with_empty_class.php';

        $this->assertEquals(
            [
                12,
            ],
            array_keys(RawCodeCoverageData::fromUncoveredFile($file, new ParsingFileAnalyser(true, true))->lineCoverage()[$file])
        );
    }

    public function testInterfacesAreUncovered(): void
    {
        $file = TEST_FILES_PATH . 'source_with_interface.php';

        $this->assertEquals(
            [
                7,
                9,
                11,
                13,
            ],
            array_keys(RawCodeCoverageData::fromUncoveredFile($file, new ParsingFileAnalyser(true, true))->lineCoverage()[$file])
        );
    }

    public function testInlineCommentsKeepTheLine(): void
    {
        $file = TEST_FILES_PATH . 'source_with_oneline_annotations.php';

        $this->assertEquals(
            [
                13,
                19,
                22,
                26,
                29,
                31,
                32,
                33,
                35,
                40,
            ],
            array_keys(RawCodeCoverageData::fromUncoveredFile($file, new ParsingFileAnalyser(true, true))->lineCoverage()[$file])
        );
    }

    public function testHeavyIndentationIsHandledCorrectly(): void
    {
        $file = TEST_FILES_PATH . 'source_with_heavy_indentation.php';

        $this->assertEquals(
            [
                14,
                19,
                // line 22 is unstable - not in xdebug output if script is cached by opcache
                25,
                // line 28 is unstable - const coverage depends on autoload order - https://github.com/sebastianbergmann/php-code-coverage/issues/889
                32,
                34,
                35,
                40,
                41,
                44,
                47,
                56,
                62,
                64,
                70,
                76,
                87,
                95,
                96,
                97,
                99,
                101,
                // line 108 is unstable - variable has no coverage if it holds const expr - https://github.com/sebastianbergmann/php-code-coverage/issues/953
                113,
                // array destruct element, should be present 114,
                117,
                127,
                132,
                133,
                134,
                135,
                136,
                137,
                138,
                141,
                146,
                148,
                149,
                154,
                157,
                162,
                // line 163 is try statement, not in xdebug output (only catch condition is covered)
                164,
                165,
                168,
                173,
                174,
                175,
                // line 176 is finally statement, not in xdebug output (only catch condition is covered)
                177,
                180,
                188,
                193,
                195,
                197,
                198,
                199,
                200,
                202,
                // line 203 is default case, not in xdebug output (only cases with condition are covered)
                204,
                207,
                216,
                218,
                220,
                222,
                224,
                226,
                228,
                230,
                232,
                234,
                256,
                261,
                265,
                268,
                272,
                276,
                282,
                286,
                292,
            ],
            array_keys(RawCodeCoverageData::fromUncoveredFile($file, new ParsingFileAnalyser(true, true))->lineCoverage()[$file])
        );
    }

    public function testEmtpyConstructorIsMarkedAsExecutable(): void
    {
        $file = TEST_FILES_PATH . 'source_with_empty_constructor.php';

        $this->assertEquals(
            [
                5,
                6,
                7,
                30,
            ],
            array_keys(RawCodeCoverageData::fromUncoveredFile($file, new ParsingFileAnalyser(true, true))->lineCoverage()[$file])
        );
    }

    /**
     * @requires PHP 8.0
     */
    public function testEachCaseInMatchExpressionIsMarkedAsExecutable(): void
    {
        $file = TEST_FILES_PATH . 'source_with_match_expression.php';

        $this->assertEquals(
            [
                14,
                20,
                25,
            ],
            array_keys(RawCodeCoverageData::fromUncoveredFile($file, new ParsingFileAnalyser(true, true))->lineCoverage()[$file])
        );
    }

    public function testReturnStatementWithOnlyAnArrayWithScalarReturnsTheFirstElementLine(): void
    {
        $file = TEST_FILES_PATH . 'source_with_return_and_array_with_scalars.php';

        $this->assertEquals(
            [
                8,
                15,
                24,
                30,
                40,
                47,
                54,
                63,
            ],
            array_keys(RawCodeCoverageData::fromUncoveredFile($file, new ParsingFileAnalyser(true, true))->lineCoverage()[$file])
        );
    }

    public function testReturnStatementWithConstantExprOnlyReturnTheLineOfLast(): void
    {
        $file = TEST_FILES_PATH . 'source_with_multiline_constant_return.php';

        $this->assertEquals(
            [
                10,
                19,
                28,
                37,
                46,
                55,
                64,
                73,
                82,
                91,
                100,
                109,
                118,
                127,
                136,
                145,
                154,
                163,
                172,
                181,
                190,
                199,
                208,
                217,
                226,
                235,
                244,
                252,
                261,
                269,
                278,
                293,
                304,
                314,
                321,
                323,
                324,
                325,
                327,
                340,
                351,
                370,
                377,
                390,
                402,
                414,
                425,
                437,
                442,
                444,
                459,
                469,
                481,
                492,
                506,
                511,
                518,
                527,
                537,
                549,
                562,
            ],
            array_keys(RawCodeCoverageData::fromUncoveredFile($file, new ParsingFileAnalyser(true, true))->lineCoverage()[$file])
        );
    }

    public function testCoverageForFileWithInlineAnnotations(): void
    {
        $filename = TEST_FILES_PATH . 'source_with_oneline_annotations.php';
        $coverage = RawCodeCoverageData::fromXdebugWithPathCoverage(
            [
                $filename => [
                    'lines' => [
                        13 => -1,
                        19 => -1,
                        22 => -1,
                        26 => -1,
                        29 => -1,
                        31 => -1,
                        32 => -1,
                        33 => -1,
                        35 => -1,
                        36 => -1,
                        37 => -1,
                    ],
                    'functions' => [
                        '{main}' => [
                            'branches' => [
                                0 => [
                                    'op_start'   => 0,
                                    'op_end'     => 0,
                                    'line_start' => 37,
                                    'line_end'   => 37,
                                    'hit'        => 0,
                                    'out'        => [
                                        0 => 2147483645,
                                    ],
                                    'out_hit' => [
                                        0 => 0,
                                    ],
                                ],
                            ],
                            'paths' => [
                                0 => [
                                    'path' => [
                                        0 => 0,
                                    ],
                                    'hit' => 0,
                                ],
                            ],
                        ],
                        'Foo->bar' => [
                            'branches' => [
                                0 => [
                                    'op_start'   => 0,
                                    'op_end'     => 2,
                                    'line_start' => 11,
                                    'line_end'   => 13,
                                    'hit'        => 0,
                                    'out'        => [
                                        0 => 2147483645,
                                    ],
                                    'out_hit' => [
                                        0 => 0,
                                    ],
                                ],
                            ],
                            'paths' => [
                                0 => [
                                    'path' => [
                                        0 => 0,
                                    ],
                                    'hit' => 0,
                                ],
                            ],
                        ],
                        'baz' => [
                            'branches' => [
                                0 => [
                                    'op_start'   => 0,
                                    'op_end'     => 18,
                                    'line_start' => 16,
                                    'line_end'   => 36,
                                    'hit'        => 0,
                                    'out'        => [
                                        0 => 2147483645,
                                    ],
                                    'out_hit' => [
                                        0 => 0,
                                    ],
                                ],
                            ],
                            'paths' => [
                                0 => [
                                    'path' => [
                                        0 => 0,
                                    ],
                                    'hit' => 0,
                                ],
                            ],
                        ],
                    ],
                ],
            ]
        );

        $coverage->removeCoverageDataForLines(
            $filename,
            [
                29,
                31,
                32,
                33,
            ]
        );

        $this->assertEquals(
            [
                13 => -1,
                19 => -1,
                22 => -1,
                26 => -1,
                35 => -1,
                36 => -1,
            ],
            $coverage->lineCoverage()[$filename]
        );

        $this->assertEquals(
            [
                '{main}' => [
                    'branches' => [
                        0 => [
                            'op_start'   => 0,
                            'op_end'     => 0,
                            'line_start' => 37,
                            'line_end'   => 37,
                            'hit'        => 0,
                            'out'        => [
                                0 => 2147483645,
                            ],
                            'out_hit' => [
                                0 => 0,
                            ],
                        ],
                    ],
                    'paths' => [
                        0 => [
                            'path' => [
                                0 => 0,
                            ],
                            'hit' => 0,
                        ],
                    ],
                ],
                'Foo->bar' => [
                    'branches' => [
                        0 => [
                            'op_start'   => 0,
                            'op_end'     => 2,
                            'line_start' => 11,
                            'line_end'   => 13,
                            'hit'        => 0,
                            'out'        => [
                                0 => 2147483645,
                            ],
                            'out_hit' => [
                                0 => 0,
                            ],
                        ],
                    ],
                    'paths' => [
                        0 => [
                            'path' => [
                                0 => 0,
                            ],
                            'hit' => 0,
                        ],
                    ],
                ],
                'baz' => [
                    'branches' => [
                    ],
                    'paths' => [
                    ],
                ],
            ],
            $coverage->functionCoverage()[$filename]
        );
    }
}
