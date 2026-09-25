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
use PHPUnit\Framework\Attributes\Ticket;
use SebastianBergmann\CodeCoverage\TestCase;

#[CoversClass(ProcessedCodeCoverageData::class)]
#[Small]
final class ProcessedCodeCoverageDataTest extends TestCase
{
    public function testDoesNotCollectHitCountsByDefault(): void
    {
        $this->assertFalse((new ProcessedCodeCoverageData)->collectsHitCounts());
    }

    public function testCollectsHitCountsWhenCreatedForDriverThatCollectsHitCounts(): void
    {
        $this->assertTrue((new ProcessedCodeCoverageData(true))->collectsHitCounts());
    }

    public function testMergedDataCollectsHitCountsWhenBothOperandsDo(): void
    {
        $coverage = new ProcessedCodeCoverageData(true);

        $coverage->merge(new ProcessedCodeCoverageData(true));

        $this->assertTrue($coverage->collectsHitCounts());
    }

    public function testMergedDataDoesNotCollectHitCountsWhenOnlyOneOperandDoes(): void
    {
        $coverage = new ProcessedCodeCoverageData(true);

        $coverage->merge(new ProcessedCodeCoverageData);

        $this->assertFalse($coverage->collectsHitCounts());

        $coverage = new ProcessedCodeCoverageData;

        $coverage->merge(new ProcessedCodeCoverageData(true));

        $this->assertFalse($coverage->collectsHitCounts());
    }

    public function testMergeWithLineCoverage(): void
    {
        $coverage = $this->getLineCoverageForBankAccountForFirstTwoTests()->getData();

        $coverage->merge($this->getLineCoverageForBankAccountForLastTwoTests()->getData());

        $this->assertEquals(
            $this->getExpectedLineCoverageDataArrayForBankAccount(),
            $this->lineCoverageKeyedByTestId($coverage),
        );
    }

    public function testMergeWithPathCoverage(): void
    {
        $coverage = $this->getPathCoverageForBankAccountForFirstTwoTests()->getData();

        $coverage->merge($this->getPathCoverageForBankAccountForLastTwoTests()->getData());

        $this->assertEquals(
            $this->functionCoverageObjectsAsArrays($this->getExpectedPathCoverageDataArrayForBankAccount()),
            $this->functionCoverageKeyedByTestId($coverage),
        );
    }

    public function testMergeWithPathCoverageIntoEmpty(): void
    {
        $coverage = new ProcessedCodeCoverageData;

        $coverage->merge($this->getPathCoverageForBankAccount()->getData());

        $this->assertEquals(
            $this->functionCoverageObjectsAsArrays($this->getExpectedPathCoverageDataArrayForBankAccount()),
            $this->functionCoverageKeyedByTestId($coverage),
        );
    }

    public function testMarkCodeAsExecutedByTestCaseInitializesPreviouslyUnseenFile(): void
    {
        $coverage = new ProcessedCodeCoverageData;

        $coverage->markCodeAsExecutedByTestCase(
            'BankAccountTest::testBalanceIsInitiallyZero',
            RawCodeCoverageData::fromLineCoverage(
                [
                    '/some/path/SomeClass.php' => [
                        8 => 1,
                        9 => -1,
                    ],
                ],
            ),
        );

        $lineCoverage = $coverage->lineCoverage();

        $this->assertArrayHasKey('/some/path/SomeClass.php', $lineCoverage);

        $fileCoverage = $lineCoverage['/some/path/SomeClass.php'];

        $this->assertArrayHasKey(8, $fileCoverage);
        $this->assertSame([0 => 1], $fileCoverage[8]);
        $this->assertArrayNotHasKey(9, $fileCoverage);
    }

    public function testSetTestIdsWithSparseIndexTableDoesNotCauseIndexCollisions(): void
    {
        $coverage = new ProcessedCodeCoverageData;

        $coverage->setTestIds([1 => 'TestA']);

        $coverage->markCodeAsExecutedByTestCase(
            'TestB',
            RawCodeCoverageData::fromLineCoverage(
                [
                    '/some/path/SomeClass.php' => [
                        8 => 1,
                    ],
                ],
            ),
        );

        $this->assertSame([1 => 'TestA', 2 => 'TestB'], $coverage->testIds());

        $lineCoverage = $coverage->lineCoverage();

        $this->assertArrayHasKey('/some/path/SomeClass.php', $lineCoverage);

        $fileCoverage = $lineCoverage['/some/path/SomeClass.php'];

        $this->assertArrayHasKey(8, $fileCoverage);
        $this->assertSame([2 => 1], $fileCoverage[8]);
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

        $lineCoverage = $existingCoverage->lineCoverage();

        $this->assertArrayHasKey('/some/path/SomeClass.php', $lineCoverage);
        $this->assertArrayHasKey(12, $lineCoverage['/some/path/SomeClass.php']);
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

        $functionCoverage = $newCoverage->functionCoverage();

        $this->assertArrayHasKey('/some/path/SomeClass.php', $functionCoverage);
        $this->assertIsArray($functionCoverage['/some/path/SomeClass.php']);
        $this->assertArrayHasKey('SomeClass->secondFunction', $functionCoverage['/some/path/SomeClass.php']);
    }

    public function testMergeOfLinesPresentInOnlyOneOfTheTwoFiles(): void
    {
        $existingCoverage = new ProcessedCodeCoverageData;
        $existingCoverage->setTestIds(['test1']);
        $existingCoverage->setLineCoverage(
            [
                '/some/path/SomeClass.php' => [
                    8 => [0 => 1],
                ],
            ],
        );

        $newCoverage = new ProcessedCodeCoverageData;
        $newCoverage->setTestIds(['test2']);
        $newCoverage->setLineCoverage(
            [
                '/some/path/SomeClass.php' => [
                    9 => [0 => 1],
                ],
            ],
        );

        $existingCoverage->merge($newCoverage);

        $lineCoverage = $this->lineCoverageKeyedByTestId($existingCoverage);

        $this->assertArrayHasKey('/some/path/SomeClass.php', $lineCoverage);

        $fileLines = $lineCoverage['/some/path/SomeClass.php'];

        $this->assertArrayHasKey(8, $fileLines);
        $this->assertArrayHasKey(9, $fileLines);
        $this->assertSame(['test1' => 1], $fileLines[8]);
        $this->assertSame(['test2' => 1], $fileLines[9]);
    }

    public function testRenameFile(): void
    {
        $coverage = new ProcessedCodeCoverageData;
        $coverage->setLineCoverage(
            [
                '/some/path/OldName.php' => [
                    8 => [0 => 1],
                ],
            ],
        );
        $coverage->setFunctionCoverage(
            [
                '/some/path/OldName.php' => [
                    'someFunction' => new ProcessedFunctionCoverageData([], []),
                ],
            ],
        );

        $coverage->renameFile('/some/path/OldName.php', '/some/path/NewName.php');

        $this->assertArrayHasKey('/some/path/NewName.php', $coverage->lineCoverage());
        $this->assertArrayNotHasKey('/some/path/OldName.php', $coverage->lineCoverage());
        $this->assertArrayHasKey('/some/path/NewName.php', $coverage->functionCoverage());
        $this->assertArrayNotHasKey('/some/path/OldName.php', $coverage->functionCoverage());
    }

    public function testRenameFileToSameNameKeepsCoverageData(): void
    {
        $coverage = new ProcessedCodeCoverageData;
        $coverage->setLineCoverage(
            [
                '/some/path/Name.php' => [
                    8 => [0 => 1],
                ],
            ],
        );
        $coverage->setFunctionCoverage(
            [
                '/some/path/Name.php' => [
                    'someFunction' => new ProcessedFunctionCoverageData([], []),
                ],
            ],
        );

        $coverage->renameFile('/some/path/Name.php', '/some/path/Name.php');

        $this->assertSame(['/some/path/Name.php' => [8 => [0 => 1]]], $coverage->lineCoverage());
        $this->assertArrayHasKey('/some/path/Name.php', $coverage->functionCoverage());
        $this->assertArrayHasKey('someFunction', $coverage->functionCoverage()['/some/path/Name.php']);
    }

    public function testRenameFileWithoutFunctionCoverage(): void
    {
        $coverage = new ProcessedCodeCoverageData;
        $coverage->setLineCoverage(
            [
                '/some/path/OldName.php' => [
                    8 => [0 => 1],
                ],
            ],
        );

        $coverage->renameFile('/some/path/OldName.php', '/some/path/NewName.php');

        $this->assertArrayHasKey('/some/path/NewName.php', $coverage->lineCoverage());
        $this->assertArrayNotHasKey('/some/path/OldName.php', $coverage->lineCoverage());
        $this->assertArrayNotHasKey('/some/path/NewName.php', $coverage->functionCoverage());
    }

    #[Ticket('https://github.com/sebastianbergmann/php-code-coverage/issues/1335')]
    public function testDataSeededFromStaticAnalysisIsReplacedByExecutedDataDuringMerge(): void
    {
        $coverage = $this->seededFromStaticAnalysis();

        $coverage->merge($this->executedByTest('test'));

        $this->assertSame(
            ['/some/path/SomeClass.php' => [10 => ['test' => 1], 11 => []]],
            $this->lineCoverageKeyedByTestId($coverage),
        );
    }

    #[Ticket('https://github.com/sebastianbergmann/php-code-coverage/issues/1335')]
    public function testDataSeededFromStaticAnalysisIsIgnoredDuringMergeWhenExecutedDataExists(): void
    {
        $coverage = $this->executedByTest('test');

        $coverage->merge($this->seededFromStaticAnalysis());

        $this->assertSame(
            ['/some/path/SomeClass.php' => [10 => ['test' => 1], 11 => []]],
            $this->lineCoverageKeyedByTestId($coverage),
        );
    }

    #[Ticket('https://github.com/sebastianbergmann/php-code-coverage/issues/1335')]
    public function testDataSeededFromStaticAnalysisRemainsReplaceableAfterMergingItWithDataSeededFromStaticAnalysis(): void
    {
        $coverage = $this->seededFromStaticAnalysis();

        $coverage->merge($this->seededFromStaticAnalysis());

        $this->assertSame(
            ['/some/path/SomeClass.php' => [9 => [], 10 => [], 11 => []]],
            $this->lineCoverageKeyedByTestId($coverage),
        );

        $coverage->merge($this->executedByTest('test'));

        $this->assertSame(
            ['/some/path/SomeClass.php' => [10 => ['test' => 1], 11 => []]],
            $this->lineCoverageKeyedByTestId($coverage),
        );
    }

    #[Ticket('https://github.com/sebastianbergmann/php-code-coverage/issues/1335')]
    public function testDataSeededFromStaticAnalysisRemainsReplaceableAfterMergingItIntoEmptyData(): void
    {
        $coverage = new ProcessedCodeCoverageData;

        $coverage->merge($this->seededFromStaticAnalysis());
        $coverage->merge($this->executedByTest('test'));

        $this->assertSame(
            ['/some/path/SomeClass.php' => [10 => ['test' => 1], 11 => []]],
            $this->lineCoverageKeyedByTestId($coverage),
        );
    }

    #[Ticket('https://github.com/sebastianbergmann/php-code-coverage/issues/1335')]
    public function testDataSeededFromStaticAnalysisRemainsReplaceableAfterRenamingTheFile(): void
    {
        $coverage = $this->seededFromStaticAnalysis('/some/path/OldName.php');

        $coverage->renameFile('/some/path/OldName.php', '/some/path/SomeClass.php');

        $coverage->merge($this->executedByTest('test'));

        $this->assertSame(
            ['/some/path/SomeClass.php' => [10 => ['test' => 1], 11 => []]],
            $this->lineCoverageKeyedByTestId($coverage),
        );
    }

    #[Ticket('https://github.com/sebastianbergmann/php-code-coverage/issues/1335')]
    public function testDataSeededFromStaticAnalysisIsNoLongerReplaceableOnceATestExecutedTheFile(): void
    {
        $coverage = $this->seededFromStaticAnalysis();

        $coverage->markCodeAsExecutedByTestCase(
            'first',
            RawCodeCoverageData::fromLineCoverage(['/some/path/SomeClass.php' => [9 => 1]]),
        );

        $coverage->merge($this->executedByTest('second'));

        $this->assertSame(
            ['/some/path/SomeClass.php' => [9 => ['first' => 1], 10 => ['second' => 1], 11 => []]],
            $this->lineCoverageKeyedByTestId($coverage),
        );
    }

    #[Ticket('https://github.com/sebastianbergmann/php-code-coverage/issues/1335')]
    public function testDataThatWasNotSeededFromStaticAnalysisIsMerged(): void
    {
        $coverage = new ProcessedCodeCoverageData;

        $coverage->initializeUnseenData(
            RawCodeCoverageData::fromLineCoverage(['/some/path/SomeClass.php' => [9 => -1]]),
        );

        $coverage->merge($this->executedByTest('test'));

        $this->assertSame(
            ['/some/path/SomeClass.php' => [9 => [], 10 => ['test' => 1], 11 => []]],
            $this->lineCoverageKeyedByTestId($coverage),
        );
    }

    /**
     * @param non-empty-string $file
     */
    private function seededFromStaticAnalysis(string $file = '/some/path/SomeClass.php'): ProcessedCodeCoverageData
    {
        $coverage = new ProcessedCodeCoverageData;

        $coverage->initializeUncoveredFiles(
            RawCodeCoverageData::fromLineCoverage([$file => [9 => -1, 10 => -1, 11 => -1]]),
        );

        return $coverage;
    }

    /**
     * @param non-empty-string $testId
     */
    private function executedByTest(string $testId): ProcessedCodeCoverageData
    {
        $data = RawCodeCoverageData::fromLineCoverage(['/some/path/SomeClass.php' => [10 => 1, 11 => -1]]);

        $coverage = new ProcessedCodeCoverageData;

        $coverage->initializeUnseenData($data);
        $coverage->markCodeAsExecutedByTestCase($testId, $data);

        return $coverage;
    }
}
