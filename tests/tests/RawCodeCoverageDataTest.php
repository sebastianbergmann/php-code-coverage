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

        $dataObject = RawCodeCoverageData::fromXdebugWithoutPathCoverage($lineDataFromDriver);
        $this->assertEquals($lineDataFromDriver, $dataObject->getLineCoverage());
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
        ];

        $lineData = [
            '/some/path/SomeClass.php' => [
                8  => 1,
                9  => -2,
                13 => -1,
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
}
