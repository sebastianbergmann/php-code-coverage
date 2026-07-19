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

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Small;
use SebastianBergmann\CodeCoverage\StaticAnalysis\FileAnalyser;
use SebastianBergmann\CodeCoverage\StaticAnalysis\ParsingSourceAnalyser;
use SebastianBergmann\CodeCoverage\TestCase;

#[CoversClass(RawCodeCoverageData::class)]
#[Small]
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

        $dataObject = RawCodeCoverageData::fromLineCoverage($lineDataFromDriver);

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

    public function testLineAndBranchDataFromGenericFormat(): void
    {
        $lineCoverage = [
            '/some/path/SomeClass.php' => [
                8 => 1,
                9 => -1,
            ],
        ];

        $functionCoverage = [
            '/some/path/SomeClass.php' => [
                '/some/path/SomeClass.php' => [
                    'branches' => [
                        0 => [
                            'op_start'   => 0,
                            'op_end'     => 0,
                            'line_start' => 8,
                            'line_end'   => 8,
                            'hit'        => 1,
                            'out'        => [],
                            'out_hit'    => [],
                        ],
                        1 => [
                            'op_start'   => 0,
                            'op_end'     => 0,
                            'line_start' => 8,
                            'line_end'   => 8,
                            'hit'        => 0,
                            'out'        => [],
                            'out_hit'    => [],
                        ],
                    ],
                    'paths' => [],
                ],
            ],
        ];

        $dataObject = RawCodeCoverageData::fromLineAndBranchCoverage($lineCoverage, $functionCoverage);

        $this->assertSame($lineCoverage, $dataObject->lineCoverage());
        $this->assertSame($functionCoverage, $dataObject->functionCoverage());
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

        $dataObject = RawCodeCoverageData::fromLineCoverage($lineDataFromDriver);

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

        $dataObject = RawCodeCoverageData::fromLineCoverage($lineDataFromDriver);

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

        $dataObject = RawCodeCoverageData::fromLineCoverage($lineDataFromDriver);

        $dataObject->keepLineCoverageDataOnlyForLines('/some/path/SomeClass.php', [9 => true, 13 => true]);
        $dataObject->keepLineCoverageDataOnlyForLines('/some/path/SomeOtherClass.php', [999 => true]);
        $dataObject->keepLineCoverageDataOnlyForLines('/some/path/AnotherClass.php', [28 => true]);

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

        $dataObject = RawCodeCoverageData::fromLineCoverage($lineDataFromDriver);

        $dataObject->removeCoverageDataForLines('/some/path/SomeClass.php', [9 => true, 13 => true]);
        $dataObject->removeCoverageDataForLines('/some/path/SomeOtherClass.php', [999 => true]);
        $dataObject->removeCoverageDataForLines('/some/path/AnotherClass.php', [28 => true]);

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
                29 => true,
                31 => true,
                32 => true,
                33 => true,
            ],
        );

        $coverage->skipEmptyLines();

        $lineCoverage = $coverage->lineCoverage();

        $this->assertArrayHasKey($filename, $lineCoverage);
        $this->assertEquals(
            [
                13 => -1,
                19 => -1,
                22 => -1,
                26 => -1,
                35 => -1,
                36 => -1,
            ],
            $lineCoverage[$filename],
        );

        $functionCoverage = $coverage->functionCoverage();

        $this->assertArrayHasKey($filename, $functionCoverage);
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
            $functionCoverage[$filename],
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

    public function testFromUncoveredFile(): void
    {
        $filename = TEST_FILES_PATH . 'BankAccount.php';
        $analyser = new FileAnalyser(new ParsingSourceAnalyser, false, false);

        $dataObject = RawCodeCoverageData::fromUncoveredFile($filename, $analyser);

        $lineCoverage = $dataObject->lineCoverage();

        $this->assertArrayHasKey($filename, $lineCoverage);
        $this->assertNotEmpty($lineCoverage[$filename]);

        foreach ($lineCoverage[$filename] as $line => $status) {
            if ($line === 32) {
                // the second return statement in withdrawMoney() is dead code
                $this->assertSame(-2, $status);

                continue;
            }

            $this->assertSame(-1, $status);
        }

        $this->assertEmpty($dataObject->functionCoverage());
    }

    public function testFromUncoveredFileMarksDeadLinesAsNotExecutable(): void
    {
        $filename = TEST_FILES_PATH . 'source_with_dead_code.php';
        $analyser = new FileAnalyser(new ParsingSourceAnalyser, false, false);

        $dataObject = RawCodeCoverageData::fromUncoveredFile($filename, $analyser);

        $lineCoverage = $dataObject->lineCoverage();

        $this->assertArrayHasKey($filename, $lineCoverage);

        $fileCoverage = $lineCoverage[$filename];

        foreach ([9, 10, 16, 22, 28, 35, 43, 50, 51, 60, 68, 69, 80, 89, 96, 103, 158] as $deadLine) {
            $this->assertArrayHasKey($deadLine, $fileCoverage, "Line {$deadLine} should be present in the coverage map");
            $this->assertSame(-2, $fileCoverage[$deadLine], "Line {$deadLine} should be marked as not executable");
        }

        foreach ([8, 15, 21, 27, 34, 42, 49, 58, 67, 72, 78, 83, 88, 95, 104, 110, 113, 151, 162, 174, 191] as $liveLine) {
            $this->assertArrayHasKey($liveLine, $fileCoverage, "Line {$liveLine} should be present in the coverage map");
            $this->assertSame(-1, $fileCoverage[$liveLine], "Line {$liveLine} should be marked as not executed");
        }
    }

    public function testKeepLineCoverageDataOnlyForLinesWithUnknownFile(): void
    {
        $lineDataFromDriver = [
            '/some/path/SomeClass.php' => [
                8  => 1,
                9  => -2,
                13 => -1,
            ],
        ];

        $dataObject = RawCodeCoverageData::fromLineCoverage($lineDataFromDriver);

        $dataObject->keepLineCoverageDataOnlyForLines('/some/path/UnknownFile.php', [8 => true]);

        $this->assertEquals($lineDataFromDriver, $dataObject->lineCoverage());
    }

    public function testMarkExecutableLineByBranchWithUnknownFile(): void
    {
        $lineDataFromDriver = [
            '/some/path/SomeClass.php' => [
                8  => 1,
                9  => -2,
                13 => -1,
            ],
        ];

        $dataObject = RawCodeCoverageData::fromLineCoverage($lineDataFromDriver);

        $dataObject->markExecutableLineByBranch('/some/path/UnknownFile.php', [8 => 0]);

        $this->assertEquals($lineDataFromDriver, $dataObject->lineCoverage());
    }

    public function testMarkExecutableLineByBranchSkipsLinesNotInBranchMap(): void
    {
        $lineDataFromDriver = [
            '/some/path/SomeClass.php' => [
                8  => 1,
                9  => -1,
                13 => -1,
            ],
        ];

        $dataObject = RawCodeCoverageData::fromLineCoverage($lineDataFromDriver);

        // Only map line 8 to branch 0 - lines 9 and 13 are not in the map
        $dataObject->markExecutableLineByBranch('/some/path/SomeClass.php', [8 => 0]);

        $lineCoverage = $dataObject->lineCoverage();

        $this->assertArrayHasKey('/some/path/SomeClass.php', $lineCoverage);

        // Line 8 was in the map and should still be present
        $this->assertArrayHasKey(8, $lineCoverage['/some/path/SomeClass.php']);
        // Lines 9 and 13 were not in the map and should have been skipped (continue)
        $this->assertArrayHasKey(9, $lineCoverage['/some/path/SomeClass.php']);
        $this->assertArrayHasKey(13, $lineCoverage['/some/path/SomeClass.php']);
    }

    public function testRemoveCoverageDataForLinesWithEmptyLines(): void
    {
        $lineDataFromDriver = [
            '/some/path/SomeClass.php' => [
                8  => 1,
                9  => -2,
                13 => -1,
            ],
        ];

        $dataObject = RawCodeCoverageData::fromLineCoverage($lineDataFromDriver);

        $dataObject->removeCoverageDataForLines('/some/path/SomeClass.php', []);

        $this->assertEquals($lineDataFromDriver, $dataObject->lineCoverage());
    }

    public function testRemoveCoverageDataForLinesWithUnknownFile(): void
    {
        $lineDataFromDriver = [
            '/some/path/SomeClass.php' => [
                8  => 1,
                9  => -2,
                13 => -1,
            ],
        ];

        $dataObject = RawCodeCoverageData::fromLineCoverage($lineDataFromDriver);

        $dataObject->removeCoverageDataForLines('/some/path/UnknownFile.php', [8 => true]);

        $this->assertEquals($lineDataFromDriver, $dataObject->lineCoverage());
    }

    public function testAddMissingExecutableLines(): void
    {
        $lineDataFromDriver = [
            '/some/path/SomeClass.php' => [
                8 => 1,
            ],
        ];

        $dataObject = RawCodeCoverageData::fromLineCoverage($lineDataFromDriver);

        $dataObject->addMissingExecutableLines('/some/path/SomeClass.php', [8 => true, 9 => true, 10 => true]);

        $lineCoverage = $dataObject->lineCoverage();

        $this->assertArrayHasKey('/some/path/SomeClass.php', $lineCoverage);

        $fileLines = $lineCoverage['/some/path/SomeClass.php'];

        $this->assertArrayHasKey(8, $fileLines);
        $this->assertArrayHasKey(9, $fileLines);
        $this->assertArrayHasKey(10, $fileLines);
        $this->assertSame(1, $fileLines[8]);
        $this->assertSame(-1, $fileLines[9]);
        $this->assertSame(-1, $fileLines[10]);
    }

    public function testAddMissingExecutableLinesWithUnknownFile(): void
    {
        $lineDataFromDriver = [
            '/some/path/SomeClass.php' => [
                8 => 1,
            ],
        ];

        $dataObject = RawCodeCoverageData::fromLineCoverage($lineDataFromDriver);

        $dataObject->addMissingExecutableLines('/some/path/UnknownFile.php', [8 => true, 9 => true]);

        $this->assertEquals($lineDataFromDriver, $dataObject->lineCoverage());
    }

    public function testMarkLinesAsNotExecutableDemotesLinesToTheDeadCodeStatus(): void
    {
        $lineDataFromDriver = [
            '/some/path/SomeClass.php' => [
                8  => 1,
                9  => -1,
                10 => -1,
            ],
        ];

        $dataObject = RawCodeCoverageData::fromLineCoverage($lineDataFromDriver);

        $dataObject->markLinesAsNotExecutable('/some/path/SomeClass.php', [9 => true, 10 => true]);

        $lineCoverage = $dataObject->lineCoverage();

        $this->assertArrayHasKey('/some/path/SomeClass.php', $lineCoverage);

        $fileCoverage = $lineCoverage['/some/path/SomeClass.php'];

        $this->assertArrayHasKey(8, $fileCoverage);
        $this->assertArrayHasKey(9, $fileCoverage);
        $this->assertArrayHasKey(10, $fileCoverage);
        $this->assertSame(1, $fileCoverage[8]);
        $this->assertSame(-2, $fileCoverage[9]);
        $this->assertSame(-2, $fileCoverage[10]);
    }

    public function testMarkLinesAsNotExecutableWithUnknownFileIsNoOp(): void
    {
        $lineDataFromDriver = [
            '/some/path/SomeClass.php' => [
                8 => 1,
            ],
        ];

        $dataObject = RawCodeCoverageData::fromLineCoverage($lineDataFromDriver);

        $dataObject->markLinesAsNotExecutable('/some/path/UnknownFile.php', [8 => true]);

        $this->assertEquals($lineDataFromDriver, $dataObject->lineCoverage());
    }

    public function testMarkExecutableLineByBranchSkipsBranchAlreadyMarkedExecuted(): void
    {
        $lineDataFromDriver = [
            '/some/path/SomeClass.php' => [
                8 => 1,
                9 => -1,
            ],
        ];

        $dataObject = RawCodeCoverageData::fromLineCoverage($lineDataFromDriver);

        // Both lines belong to branch 0. Line 8 is executed, which marks the
        // branch as executed and removes it from the lookup, so line 9 hits the
        // "branch already removed" early continue.
        $dataObject->markExecutableLineByBranch('/some/path/SomeClass.php', [8 => 0, 9 => 0]);

        $lineCoverage = $dataObject->lineCoverage();

        $this->assertArrayHasKey('/some/path/SomeClass.php', $lineCoverage);

        $fileLines = $lineCoverage['/some/path/SomeClass.php'];

        $this->assertArrayHasKey(8, $fileLines);
        $this->assertArrayHasKey(9, $fileLines);
        $this->assertSame(1, $fileLines[8]);
        $this->assertSame(1, $fileLines[9]);
    }

    public function testKeepFunctionCoverageDataOnlyForLines(): void
    {
        $filename = '/some/path/SomeClass.php';

        $rawDataFromDriver = [
            $filename => [
                'lines' => [
                    11 => 1,
                    12 => 1,
                ],
                'functions' => [
                    'foo' => [
                        'branches' => [
                            0 => [
                                'op_start'   => 0,
                                'op_end'     => 5,
                                'line_start' => 11,
                                'line_end'   => 12,
                                'hit'        => 1,
                                'out'        => [],
                                'out_hit'    => [],
                            ],
                        ],
                        'paths' => [
                            0 => [
                                'path' => [0 => 0],
                                'hit'  => 1,
                            ],
                        ],
                    ],
                ],
            ],
        ];

        $dataObject = RawCodeCoverageData::fromXdebugWithPathCoverage($rawDataFromDriver);

        // Branch 0 spans lines 11-12, but only line 11 is kept, so the branch
        // and the path referencing it are removed.
        $dataObject->keepFunctionCoverageDataOnlyForLines($filename, [11 => true]);

        $functionCoverage = $dataObject->functionCoverage();

        $this->assertArrayHasKey($filename, $functionCoverage);
        $this->assertArrayHasKey('foo', $functionCoverage[$filename]);

        $functionData = $functionCoverage[$filename]['foo'];

        $this->assertEmpty($functionData['branches']);
        $this->assertEmpty($functionData['paths']);
    }

    public function testKeepFunctionCoverageDataOnlyForLinesKeepsBranchWhoseLinesAreAllIncluded(): void
    {
        $filename = '/some/path/SomeClass.php';

        $rawDataFromDriver = [
            $filename => [
                'lines' => [
                    11 => 1,
                    12 => 1,
                    13 => 1,
                ],
                'functions' => [
                    'foo' => [
                        'branches' => [
                            0 => [
                                'op_start'   => 0,
                                'op_end'     => 5,
                                'line_start' => 11,
                                'line_end'   => 11,
                                'hit'        => 1,
                                'out'        => [],
                                'out_hit'    => [],
                            ],
                            1 => [
                                'op_start'   => 6,
                                'op_end'     => 10,
                                'line_start' => 12,
                                'line_end'   => 13,
                                'hit'        => 1,
                                'out'        => [],
                                'out_hit'    => [],
                            ],
                        ],
                        'paths' => [
                            0 => [
                                'path' => [0 => 0],
                                'hit'  => 1,
                            ],
                            1 => [
                                'path' => [0 => 0, 1 => 1],
                                'hit'  => 1,
                            ],
                        ],
                    ],
                ],
            ],
        ];

        $dataObject = RawCodeCoverageData::fromXdebugWithPathCoverage($rawDataFromDriver);

        // Branch 0 spans only line 11, which is kept, so it survives. Branch 1
        // spans lines 12-13, but line 13 is not kept, so it is removed together
        // with the path referencing it.
        $dataObject->keepFunctionCoverageDataOnlyForLines($filename, [11 => true, 12 => true]);

        $functionCoverage = $dataObject->functionCoverage();

        $this->assertArrayHasKey($filename, $functionCoverage);
        $this->assertArrayHasKey('foo', $functionCoverage[$filename]);

        $functionData = $functionCoverage[$filename]['foo'];

        $this->assertArrayHasKey(0, $functionData['branches']);
        $this->assertArrayNotHasKey(1, $functionData['branches']);
        $this->assertArrayHasKey(0, $functionData['paths']);
        $this->assertArrayNotHasKey(1, $functionData['paths']);
    }

    public function testBranchWithInvertedLineRangeIsNormalized(): void
    {
        $filename = '/some/path/SomeClass.php';

        // Xdebug reports loop back-edge branches with line_start > line_end
        $rawDataFromDriver = [
            $filename => [
                'lines' => [
                    11 => 1,
                    12 => 1,
                ],
                'functions' => [
                    'foo' => [
                        'branches' => [
                            0 => [
                                'op_start'   => 0,
                                'op_end'     => 5,
                                'line_start' => 12,
                                'line_end'   => 11,
                                'hit'        => 1,
                                'out'        => [],
                                'out_hit'    => [],
                            ],
                        ],
                        'paths' => [
                            0 => [
                                'path' => [0 => 0],
                                'hit'  => 1,
                            ],
                        ],
                    ],
                ],
            ],
        ];

        $dataObject = RawCodeCoverageData::fromXdebugWithPathCoverage($rawDataFromDriver);

        $functionCoverage = $dataObject->functionCoverage();

        $this->assertArrayHasKey($filename, $functionCoverage);
        $this->assertArrayHasKey('foo', $functionCoverage[$filename]);
        $this->assertArrayHasKey(0, $functionCoverage[$filename]['foo']['branches']);

        $branch = $functionCoverage[$filename]['foo']['branches'][0];

        $this->assertSame(11, $branch['line_start']);
        $this->assertSame(12, $branch['line_end']);
    }

    public function testKeepFunctionCoverageDataOnlyForLinesWithUnknownFile(): void
    {
        $rawDataFromDriver = [
            '/some/path/SomeClass.php' => [
                'lines' => [
                    11 => 1,
                ],
                'functions' => [
                ],
            ],
        ];

        $dataObject = RawCodeCoverageData::fromXdebugWithPathCoverage($rawDataFromDriver);

        $dataObject->keepFunctionCoverageDataOnlyForLines('/some/path/UnknownFile.php', [11 => true]);

        $this->assertArrayNotHasKey('/some/path/UnknownFile.php', $dataObject->functionCoverage());
    }
}
