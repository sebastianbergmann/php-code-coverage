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

class RawCodeCoverageDataTest extends TestCase
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

        $dataObject = new RawCodeCoverageData($lineDataFromDriver);
        $this->assertEquals($lineDataFromDriver, $dataObject->getLineCoverage());
    }

    /**
     * In the branch-check XDebug format, the line data exists inside a "lines" array key.
     */
    public function testLineDataFromBranchCheckXDebugFormat(): void
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
        ];

        $lineData = [
            '/some/path/SomeClass.php' => [
                8  => 1,
                9  => -2,
                13 => -1,
            ],
        ];

        $dataObject = new RawCodeCoverageData($rawDataFromDriver);
        $this->assertEquals($lineData, $dataObject->getLineCoverage());
    }

    /**
     * Coverage data that does not match a known format should throw an exception.
     */
    public function testDataFromUnknownFormat(): void
    {
        $this->expectException(UnknownCoverageDataFormatException::class);

        $lineDataFromDriver = [
            '/some/path/SomeClass.php' => [
                'executedLines' => [
                    8,
                ],
                'unExecutedLines' => [
                    13,
                ],
                'nonExecutableLines' => [
                    9,
                ],
            ],
        ];

        $dataObject = new RawCodeCoverageData($lineDataFromDriver);
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

        $dataObject = new RawCodeCoverageData($lineDataFromDriver);
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

        $dataObject = new RawCodeCoverageData($lineDataFromDriver);
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

        $dataObject = new RawCodeCoverageData($lineDataFromDriver);
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

        $dataObject = new RawCodeCoverageData($lineDataFromDriver);
        $dataObject->removeCoverageDataForLines('/some/path/SomeClass.php', [9, 13]);
        $dataObject->removeCoverageDataForLines('/some/path/SomeOtherClass.php', [999]);
        $dataObject->removeCoverageDataForLines('/some/path/AnotherClass.php', [28]);
        $this->assertEquals($expectedFilterResult, $dataObject->getLineCoverage());
    }
}
