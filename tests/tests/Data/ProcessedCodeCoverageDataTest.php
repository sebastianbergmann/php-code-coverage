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

final class ProcessedCodeCoverageDataTest extends TestCase
{
    public function testMergeWithLineCoverage(): void
    {
        $coverage = $this->getLineCoverageForBankAccountForFirstTwoTests()->getData();

        $coverage->merge($this->getLineCoverageForBankAccountForLastTwoTests()->getData());

        $this->assertEquals(
            $this->getExpectedLineCoverageDataArrayForBankAccount(),
            $coverage->lineCoverage(),
        );
    }

    public function testMergeWithPathCoverage(): void
    {
        $coverage = $this->getPathCoverageForBankAccountForFirstTwoTests()->getData();

        $coverage->merge($this->getPathCoverageForBankAccountForLastTwoTests()->getData());

        $this->assertEquals(
            $this->getExpectedPathCoverageDataArrayForBankAccount(),
            $coverage->functionCoverage(),
        );
    }

    public function testMergeWithPathCoverageIntoEmpty(): void
    {
        $coverage = new ProcessedCodeCoverageData;

        $coverage->merge($this->getPathCoverageForBankAccount()->getData());

        $this->assertEquals(
            $this->getExpectedPathCoverageDataArrayForBankAccount(),
            $coverage->functionCoverage(),
        );
    }

    public function testMergeOfAPreviouslyUnseenLine(): void
    {
        $newCoverage = new ProcessedCodeCoverageData;

        $newCoverage->setLineCoverage(
            [
                '/some/path/SomeClass.php' => [
                    12 => [],
                    34 => null,
                ],
            ],
        );

        $existingCoverage = new ProcessedCodeCoverageData;

        $existingCoverage->merge($newCoverage);

        $this->assertArrayHasKey(12, $existingCoverage->lineCoverage()['/some/path/SomeClass.php']);
    }

    public function testMergeDoesNotCrashWhenFileContentsHaveChanged(): void
    {
        $coverage = new ProcessedCodeCoverageData;
        $coverage->setFunctionCoverage(
            [
                '/some/path/SomeClass.php' => [
                    'SomeClass->firstFunction' => new ProcessedFunctionCoverageData(
                        [
                            0 => new ProcessedBranchCoverageData(
                                0,
                                14,
                                20,
                                25,
                                [],
                                [],
                                [],
                            ),
                        ],
                        [
                            0 => new ProcessedPathCoverageData(
                                [
                                    0 => 0,
                                ],
                                [],
                            ),
                        ],
                    ),
                ],
            ],
        );

        $newCoverage = new ProcessedCodeCoverageData;
        $newCoverage->setFunctionCoverage(
            [
                '/some/path/SomeClass.php' => [
                    'SomeClass->firstFunction' => new ProcessedFunctionCoverageData(
                        [
                            0 => new ProcessedBranchCoverageData(
                                0,
                                14,
                                20,
                                25,
                                [],
                                [],
                                [],
                            ),
                            1 => new ProcessedBranchCoverageData(
                                15,
                                16,
                                26,
                                27,
                                [],
                                [],
                                [],
                            ),
                        ],
                        [
                            0 => new ProcessedPathCoverageData(
                                [
                                    0 => 0,
                                ],
                                [],
                            ),
                            1 => new ProcessedPathCoverageData(
                                [
                                    0 => 1,
                                ],
                                [],
                            ),
                        ],
                    ),
                    'SomeClass->secondFunction' => new ProcessedFunctionCoverageData(
                        [
                            0 => new ProcessedBranchCoverageData(
                                0,
                                24,
                                30,
                                35,
                                [],
                                [],
                                [],
                            ),
                        ],
                        [
                            0 => new ProcessedPathCoverageData(
                                [
                                    0 => 0,
                                ],
                                [],
                            ),
                        ],
                    ),
                ],
            ],
        );

        $coverage->merge($newCoverage);

        $this->assertIsArray($newCoverage->functionCoverage()['/some/path/SomeClass.php']);
        $this->assertArrayHasKey('SomeClass->secondFunction', $newCoverage->functionCoverage()['/some/path/SomeClass.php']);
    }
}
