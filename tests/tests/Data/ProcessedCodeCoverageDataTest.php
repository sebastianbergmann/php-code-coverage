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
                    'SomeClass->firstFunction' => [
                        'branches' => [
                            0 => [
                                'op_start'   => 0,
                                'op_end'     => 14,
                                'line_start' => 20,
                                'line_end'   => 25,
                                'hit'        => [],
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
                                ],
                                'hit' => [],
                            ],
                        ],
                    ],
                ],
            ],
        );

        $newCoverage = new ProcessedCodeCoverageData;
        $newCoverage->setFunctionCoverage(
            [
                '/some/path/SomeClass.php' => [
                    'SomeClass->firstFunction' => [
                        'branches' => [
                            0 => [
                                'op_start'   => 0,
                                'op_end'     => 14,
                                'line_start' => 20,
                                'line_end'   => 25,
                                'hit'        => [],
                                'out'        => [
                                ],
                                'out_hit' => [
                                ],
                            ],
                            1 => [
                                'op_start'   => 15,
                                'op_end'     => 16,
                                'line_start' => 26,
                                'line_end'   => 27,
                                'hit'        => [],
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
                                ],
                                'hit' => [],
                            ],
                            1 => [
                                'path' => [
                                    0 => 1,
                                ],
                                'hit' => [],
                            ],
                        ],
                    ],
                    'SomeClass->secondFunction' => [
                        'branches' => [
                            0 => [
                                'op_start'   => 0,
                                'op_end'     => 24,
                                'line_start' => 30,
                                'line_end'   => 35,
                                'hit'        => [],
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
                                ],
                                'hit' => [],
                            ],
                        ],
                    ],
                ],
            ],
        );

        $coverage->merge($newCoverage);

        $this->assertArrayHasKey('SomeClass->secondFunction', $newCoverage->functionCoverage()['/some/path/SomeClass.php']);
    }
}
