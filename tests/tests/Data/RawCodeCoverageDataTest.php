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
            ],
        );

        $coverage->removeCoverageDataForLines(
            $filename,
            [
                29,
                31,
                32,
                33,
            ],
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
            $coverage->lineCoverage()[$filename],
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
            $coverage->functionCoverage()[$filename],
        );
    }

    /**
     * Xdebug annotates function names inside trait classes.
     */
    public function testTraitFunctionNamesDecodedPathCoverageXDebugFormat(): void
    {
        $rawDataFromDriver = [
            '/some/path/FooTrait.php' => [
                'lines' => [
                    11 => 1,
                    12 => -1,
                    15 => 1,
                    16 => -2,
                    18 => 1,
                ],
                'functions' => [
                    'App\\FooTrait->returnsTrue{trait-method:/some/path/FooTrait.php:9-16}' => [
                        'branches' => [
                            0 => [
                                'op_start'   => 0,
                                'op_end'     => 5,
                                'line_start' => 11,
                                'line_end'   => 11,
                                'hit'        => 1,
                                'out'        => [
                                    0 => 6,
                                    1 => 8,
                                ],
                                'out_hit' => [
                                    0 => 0,
                                    1 => 1,
                                ],
                            ],
                            6 => [
                                'op_start'   => 6,
                                'op_end'     => 7,
                                'line_start' => 12,
                                'line_end'   => 12,
                                'hit'        => 0,
                                'out'        => [
                                    0 => 2147483645,
                                ],
                                'out_hit' => [
                                    0 => 0,
                                ],
                            ],
                            8 => [
                                'op_start'   => 8,
                                'op_end'     => 12,
                                'line_start' => 15,
                                'line_end'   => 16,
                                'hit'        => 1,
                                'out'        => [
                                ],
                                'out_hit' => [
                                ],
                            ],
                        ],
                        'paths' => [
                            0 => [
                                'path' => [
                                    0 => 0,
                                    1 => 6,
                                ],
                                'hit' => 0,
                            ],
                            1 => [
                                'path' => [
                                    0 => 0,
                                    1 => 8,
                                ],
                                'hit' => 1,
                            ],
                        ],
                    ],
                    '{main}' => [
                        'branches' => [
                            0 => [
                                'op_start'   => 0,
                                'op_end'     => 2,
                                'line_start' => 3,
                                'line_end'   => 18,
                                'hit'        => 1,
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
                                'hit' => 1,
                            ],
                        ],
                    ],
                ],
            ],
        ];

        $functionData = [
            '/some/path/FooTrait.php' => [
                'App\\FooTrait->returnsTrue' => [
                    'branches' => [
                        0 => [
                            'op_start'   => 0,
                            'op_end'     => 5,
                            'line_start' => 11,
                            'line_end'   => 11,
                            'hit'        => 1,
                            'out'        => [
                                0 => 6,
                                1 => 8,
                            ],
                            'out_hit' => [
                                0 => 0,
                                1 => 1,
                            ],
                        ],
                        6 => [
                            'op_start'   => 6,
                            'op_end'     => 7,
                            'line_start' => 12,
                            'line_end'   => 12,
                            'hit'        => 0,
                            'out'        => [
                                0 => 2147483645,
                            ],
                            'out_hit' => [
                                0 => 0,
                            ],
                        ],
                        8 => [
                            'op_start'   => 8,
                            'op_end'     => 12,
                            'line_start' => 15,
                            'line_end'   => 16,
                            'hit'        => 1,
                            'out'        => [
                            ],
                            'out_hit' => [
                            ],
                        ],
                    ],
                    'paths' => [
                        0 => [
                            'path' => [
                                0 => 0,
                                1 => 6,
                            ],
                            'hit' => 0,
                        ],
                        1 => [
                            'path' => [
                                0 => 0,
                                1 => 8,
                            ],
                            'hit' => 1,
                        ],
                    ],
                ],
                '{main}' => [
                    'branches' => [
                        0 => [
                            'op_start'   => 0,
                            'op_end'     => 2,
                            'line_start' => 3,
                            'line_end'   => 18,
                            'hit'        => 1,
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
                            'hit' => 1,
                        ],
                    ],
                ],
            ],
        ];

        $dataObject = RawCodeCoverageData::fromXdebugWithPathCoverage($rawDataFromDriver);

        $this->assertEquals($functionData, $dataObject->functionCoverage());
    }
}
