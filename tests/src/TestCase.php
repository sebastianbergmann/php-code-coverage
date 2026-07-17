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

use const DIRECTORY_SEPARATOR;
use function is_dir;
use function rmdir;
use function unlink;
use BankAccount;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use SebastianBergmann\CodeCoverage\Data\ProcessedBranchCoverageData;
use SebastianBergmann\CodeCoverage\Data\ProcessedCodeCoverageData;
use SebastianBergmann\CodeCoverage\Data\ProcessedFunctionCoverageData;
use SebastianBergmann\CodeCoverage\Data\ProcessedPathCoverageData;
use SebastianBergmann\CodeCoverage\Data\RawCodeCoverageData;
use SebastianBergmann\CodeCoverage\Driver\Driver;
use SebastianBergmann\CodeCoverage\Node\Builder;
use SebastianBergmann\CodeCoverage\Node\Directory as DirectoryNode;
use SebastianBergmann\CodeCoverage\StaticAnalysis\FileAnalyser;
use SebastianBergmann\CodeCoverage\StaticAnalysis\ParsingSourceAnalyser;
use SebastianBergmann\CodeCoverage\Test\Target\Target;
use SebastianBergmann\CodeCoverage\Test\Target\TargetCollection;
use SomeNamespace\BankAccountTrait;

abstract class TestCase extends \PHPUnit\Framework\TestCase
{
    protected function getLineCoverageXdebugDataForBankAccount()
    {
        return new CoverageFixtureProvider()->lineCoverageXdebugDataForBankAccount();
    }

    protected function getPathCoverageXdebugDataForBankAccount()
    {
        return new CoverageFixtureProvider()->pathCoverageXdebugDataForBankAccount();
    }

    protected function getPathCoverageXdebugDataForSourceWithoutNamespace()
    {
        return new CoverageFixtureProvider()->pathCoverageXdebugDataForSourceWithoutNamespace();
    }

    protected function getLineCoverageForBankAccount(): CodeCoverage
    {
        return new CoverageFixtureProvider()->lineCoverageForBankAccount();
    }

    protected function getPathCoverageForBankAccount(): CodeCoverage
    {
        return new CoverageFixtureProvider()->pathCoverageForBankAccount();
    }

    protected function getPathCoverageForSourceWithoutNamespace(): CodeCoverage
    {
        return new CoverageFixtureProvider()->pathCoverageForSourceWithoutNamespace();
    }

    protected function getXdebugDataForNamespacedBankAccount()
    {
        return [
            RawCodeCoverageData::fromXdebugWithoutPathCoverage([
                TEST_FILES_PATH . 'NamespacedBankAccount.php' => [
                    13 => 1,
                    14 => -2,
                    18 => -1,
                    19 => -1,
                    20 => -1,
                    21 => -1,
                    23 => -1,
                    27 => -1,
                    29 => -1,
                    30 => -2,
                    34 => -1,
                    36 => -1,
                    37 => -2,
                ],
            ]),
            RawCodeCoverageData::fromXdebugWithoutPathCoverage([
                TEST_FILES_PATH . 'NamespacedBankAccount.php' => [
                    13 => 1,
                    18 => 1,
                    21 => 1,
                    34 => 1,
                ],
            ]),
            RawCodeCoverageData::fromXdebugWithoutPathCoverage([
                TEST_FILES_PATH . 'NamespacedBankAccount.php' => [
                    13 => 1,
                    18 => 1,
                    21 => 1,
                    27 => 1,
                ],
            ]),
            RawCodeCoverageData::fromXdebugWithoutPathCoverage([
                TEST_FILES_PATH . 'NamespacedBankAccount.php' => [
                    13 => 1,
                    18 => 1,
                    19 => 1,
                    20 => 1,
                    23 => 1,
                    27 => 1,
                    29 => 1,
                    34 => 1,
                    36 => 1,
                ],
            ]),
        ];
    }

    protected function getLineCoverageForNamespacedBankAccount(): CodeCoverage
    {
        $data = $this->getXdebugDataForNamespacedBankAccount();

        $stub = $this->createStub(Driver::class);

        $stub
            ->method('stop')
            ->willReturn(...$data);

        $filter = new Filter;
        $filter->includeFile(TEST_FILES_PATH . 'NamespacedBankAccount.php');

        $coverage = new CodeCoverage($stub, $filter);

        $coverage->start(
            'BankAccountTest::testBalanceIsInitiallyZero',
            null,
            true,
        );

        $coverage->stop(
            true,
            null,
            TargetCollection::fromArray([
                Target::forMethod(BankAccountTrait::class, 'getBalance'),
            ]),
        );

        $coverage->start(
            'BankAccountTest::testBalanceCannotBecomeNegative',
        );

        $coverage->stop(
            true,
            null,
            TargetCollection::fromArray([
                Target::forMethod(BankAccountTrait::class, 'withdrawMoney'),
            ]),
            time: 0.9,
        );

        $coverage->start(
            'BankAccountTest::testBalanceCannotBecomeNegative2',
        );

        $coverage->stop(
            true,
            null,
            TargetCollection::fromArray([
                Target::forMethod(BankAccountTrait::class, 'depositMoney'),
            ]),
            time: 1.0,
        );

        $coverage->start(
            'BankAccountTest::testDepositWithdrawMoney',
        );

        $coverage->stop(
            true,
            null,
            TargetCollection::fromArray([
                Target::forMethod(BankAccountTrait::class, 'getBalance'),
                Target::forMethod(BankAccountTrait::class, 'depositMoney'),
                Target::forMethod(BankAccountTrait::class, 'withdrawMoney'),
            ]),
            time: 1.1,
        );

        return $coverage;
    }

    protected function getLineCoverageForBankAccountForFirstTwoTests(): CodeCoverage
    {
        $data = $this->getLineCoverageXdebugDataForBankAccount();

        $stub = $this->createStub(Driver::class);

        $stub
            ->method('stop')
            ->willReturn(...$data);

        $filter = new Filter;
        $filter->includeFile(TEST_FILES_PATH . 'BankAccount.php');

        $coverage = new CodeCoverage($stub, $filter);

        $coverage->start(
            'BankAccountTest::testBalanceIsInitiallyZero',
            null,
            true,
        );

        $coverage->stop(
            true,
            null,
            TargetCollection::fromArray(
                [
                    Target::forMethod(BankAccount::class, 'getBalance'),
                ],
            ),
            time: 1.2,
        );

        $coverage->start(
            'BankAccountTest::testBalanceCannotBecomeNegative',
        );

        $coverage->stop(
            true,
            null,
            TargetCollection::fromArray(
                [
                    Target::forMethod(BankAccount::class, 'withdrawMoney'),
                ],
            ),
            time: 1.3,
        );

        return $coverage;
    }

    protected function getLineCoverageForBankAccountForLastTwoTests(): CodeCoverage
    {
        $data = $this->getLineCoverageXdebugDataForBankAccount();

        $stub = $this->createStub(Driver::class);

        $stub
            ->method('stop')
            ->willReturn($data[2], $data[3]);

        $filter = new Filter;
        $filter->includeFile(TEST_FILES_PATH . 'BankAccount.php');

        $coverage = new CodeCoverage($stub, $filter);

        $coverage->start(
            'BankAccountTest::testBalanceCannotBecomeNegative2',
        );

        $coverage->stop(
            true,
            null,
            TargetCollection::fromArray(
                [
                    Target::forMethod(BankAccount::class, 'depositMoney'),
                ],
            ),
            time: 1.4,
        );

        $coverage->start(
            'BankAccountTest::testDepositWithdrawMoney',
        );

        $coverage->stop(
            true,
            null,
            TargetCollection::fromArray(
                [
                    Target::forMethod(BankAccount::class, 'getBalance'),
                    Target::forMethod(BankAccount::class, 'depositMoney'),
                    Target::forMethod(BankAccount::class, 'withdrawMoney'),
                ],
            ),
            time: 1.5,
        );

        return $coverage;
    }

    protected function getExpectedLineCoverageDataArrayForBankAccount(): array
    {
        return [
            TEST_FILES_PATH . 'BankAccount.php' => [
                8 => [
                    'BankAccountTest::testBalanceIsInitiallyZero' => 1,
                    'BankAccountTest::testDepositWithdrawMoney'   => 1,
                ],
                13 => [],
                14 => [],
                16 => [],
                22 => [
                    'BankAccountTest::testBalanceCannotBecomeNegative2' => 1,
                    'BankAccountTest::testDepositWithdrawMoney'         => 1,
                ],
                24 => [
                    'BankAccountTest::testDepositWithdrawMoney' => 1,
                ],
                29 => [
                    'BankAccountTest::testBalanceCannotBecomeNegative' => 1,
                    'BankAccountTest::testDepositWithdrawMoney'        => 1,
                ],
                31 => [
                    'BankAccountTest::testDepositWithdrawMoney' => 1,
                ],
                32 => null,
            ],
        ];
    }

    protected function getExpectedLineCoverageDataArrayForBankAccountInReverseOrder(): array
    {
        return [
            TEST_FILES_PATH . 'BankAccount.php' => [
                8 => [
                    'BankAccountTest::testDepositWithdrawMoney'   => 1,
                    'BankAccountTest::testBalanceIsInitiallyZero' => 1,
                ],
                13 => [],
                14 => [],
                16 => [],
                22 => [
                    'BankAccountTest::testBalanceCannotBecomeNegative2' => 1,
                    'BankAccountTest::testDepositWithdrawMoney'         => 1,
                ],
                24 => [
                    'BankAccountTest::testDepositWithdrawMoney' => 1,
                ],
                29 => [
                    'BankAccountTest::testDepositWithdrawMoney'        => 1,
                    'BankAccountTest::testBalanceCannotBecomeNegative' => 1,
                ],
                31 => [
                    'BankAccountTest::testDepositWithdrawMoney' => 1,
                ],
                32 => null,
            ],
        ];
    }

    protected function getPathCoverageForBankAccountForFirstTwoTests(): CodeCoverage
    {
        $data = $this->getPathCoverageXdebugDataForBankAccount();

        $stub = $this->createStub(Driver::class);

        $stub
            ->method('stop')
            ->willReturn(...$data);

        $filter = new Filter;
        $filter->includeFile(TEST_FILES_PATH . 'BankAccount.php');

        $coverage = new CodeCoverage($stub, $filter);

        $coverage->enableBranchAndPathCoverage();

        $coverage->start(
            'BankAccountTest::testBalanceIsInitiallyZero',
            null,
            true,
        );

        $coverage->stop(
            true,
            null,
            TargetCollection::fromArray(
                [
                    Target::forMethod(BankAccount::class, 'getBalance'),
                ],
            ),
            time: 1.6,
        );

        $coverage->start(
            'BankAccountTest::testBalanceCannotBecomeNegative',
        );

        $coverage->stop(
            true,
            null,
            TargetCollection::fromArray(
                [
                    Target::forMethod(BankAccount::class, 'withdrawMoney'),
                ],
            ),
            time: 1.7,
        );

        return $coverage;
    }

    protected function getPathCoverageForBankAccountForLastTwoTests(): CodeCoverage
    {
        $data = $this->getPathCoverageXdebugDataForBankAccount();

        $stub = $this->createStub(Driver::class);

        $stub
            ->method('stop')
            ->willReturn($data[2], $data[3]);

        $filter = new Filter;
        $filter->includeFile(TEST_FILES_PATH . 'BankAccount.php');

        $coverage = new CodeCoverage($stub, $filter);

        $coverage->enableBranchAndPathCoverage();

        $coverage->start(
            'BankAccountTest::testBalanceCannotBecomeNegative2',
        );

        $coverage->stop(
            true,
            null,
            TargetCollection::fromArray(
                [
                    Target::forMethod(BankAccount::class, 'depositMoney'),
                ],
            ),
            time: 1.8,
        );

        $coverage->start(
            'BankAccountTest::testDepositWithdrawMoney',
        );

        $coverage->stop(
            true,
            null,
            TargetCollection::fromArray(
                [
                    Target::forMethod(BankAccount::class, 'getBalance'),
                    Target::forMethod(BankAccount::class, 'depositMoney'),
                    Target::forMethod(BankAccount::class, 'withdrawMoney'),
                ],
            ),
            time: 1.9,
        );

        return $coverage;
    }

    protected function getExpectedPathCoverageDataArrayForBankAccount(): array
    {
        return [
            TEST_FILES_PATH . 'BankAccount.php' => [
                'BankAccount->depositMoney' => new ProcessedFunctionCoverageData(
                    [
                        0 => new ProcessedBranchCoverageData(
                            0,
                            14,
                            20,
                            25,
                            [
                                'BankAccountTest::testBalanceCannotBecomeNegative2' => 1,
                                'BankAccountTest::testDepositWithdrawMoney'         => 1,
                            ],
                            [],
                            [],
                        ),
                    ],
                    [
                        0 => new ProcessedPathCoverageData(
                            [
                                0 => 0,
                            ],
                            [
                                'BankAccountTest::testBalanceCannotBecomeNegative2' => 1,
                                'BankAccountTest::testDepositWithdrawMoney'         => 1,
                            ],
                        ),
                    ],
                ),
                'BankAccount->getBalance' => new ProcessedFunctionCoverageData(
                    [
                        0 => new ProcessedBranchCoverageData(
                            0,
                            5,
                            6,
                            9,
                            [
                                'BankAccountTest::testBalanceIsInitiallyZero' => 1,
                                'BankAccountTest::testDepositWithdrawMoney'   => 1,
                            ],
                            [
                            ],
                            [
                            ],
                        ),
                    ],
                    [
                        0 => new ProcessedPathCoverageData(
                            [
                                0 => 0,
                            ],
                            [
                                'BankAccountTest::testBalanceIsInitiallyZero' => 1,
                                'BankAccountTest::testDepositWithdrawMoney'   => 1,
                            ],
                        ),
                    ],
                ),
                'BankAccount->withdrawMoney' => new ProcessedFunctionCoverageData(
                    [
                        0 => new ProcessedBranchCoverageData(
                            0,
                            14,
                            27,
                            32,
                            [
                                'BankAccountTest::testBalanceCannotBecomeNegative' => 1,
                                'BankAccountTest::testDepositWithdrawMoney'        => 1,
                            ],
                            [
                            ],
                            [
                            ],
                        ),
                    ],
                    [
                        0 => new ProcessedPathCoverageData(
                            [
                                0 => 0,
                            ],
                            [
                                'BankAccountTest::testBalanceCannotBecomeNegative' => 1,
                                'BankAccountTest::testDepositWithdrawMoney'        => 1,
                            ],
                        ),
                    ],
                ),
                '{main}' => new ProcessedFunctionCoverageData(
                    [
                        0 => new ProcessedBranchCoverageData(
                            0,
                            1,
                            34,
                            34,
                            [
                            ],
                            [
                                0 => 2147483645,
                            ],
                            [
                                0 => 0,
                            ],
                        ),
                    ],
                    [
                        0 => new ProcessedPathCoverageData(
                            [
                                0 => 0,
                            ],
                            [
                            ],
                        ),
                    ],
                ),
                'BankAccount->setBalance' => new ProcessedFunctionCoverageData(
                    [
                        0 => new ProcessedBranchCoverageData(
                            0,
                            4,
                            11,
                            13,
                            [
                            ],
                            [
                                0 => 5,
                                1 => 9,
                            ],
                            [
                                0 => 0,
                                1 => 0,
                            ],
                        ),
                        5 => new ProcessedBranchCoverageData(
                            5,
                            8,
                            14,
                            14,
                            [
                            ],
                            [
                                0 => 13,
                            ],
                            [
                                0 => 0,
                            ],
                        ),
                        9 => new ProcessedBranchCoverageData(
                            9,
                            12,
                            16,
                            16,
                            [
                            ],
                            [
                                0 => 2147483645,
                            ],
                            [
                                0 => 0,
                            ],
                        ),
                        13 => new ProcessedBranchCoverageData(
                            13,
                            14,
                            18,
                            18,
                            [
                            ],
                            [
                                0 => 2147483645,
                            ],
                            [
                                0 => 0,
                            ],
                        ),
                    ],
                    [
                        0 => new ProcessedPathCoverageData(
                            [
                                0 => 0,
                                1 => 5,
                                2 => 13,
                            ],
                            [
                            ],
                        ),
                        1 => new ProcessedPathCoverageData(
                            [
                                0 => 0,
                                1 => 9,
                            ],
                            [
                            ],
                        ),
                    ],
                ),
            ],
        ];
    }

    protected function getCoverageForFileWithIgnoredLines(): CodeCoverage
    {
        return new CoverageFixtureProvider()->coverageForFileWithIgnoredLines();
    }

    protected function getLineCoverageForFileWithEval(): CodeCoverage
    {
        $filter = new Filter;
        $filter->includeFile(TEST_FILES_PATH . 'source_with_eval.php');

        $coverage = new CodeCoverage(
            $this->setUpXdebugStubForFileWithEval(),
            $filter,
        );

        $coverage->start('FileWithEval', null, true);
        $coverage->stop();

        return $coverage;
    }

    protected function setUpXdebugStubForFileWithEval(): Driver
    {
        $stub = $this->createStub(Driver::class);

        $stub
            ->method('stop')
            ->willReturn(RawCodeCoverageData::fromXdebugWithoutPathCoverage(
                [
                    TEST_FILES_PATH . 'source_with_eval.php' => [
                        3 => 1,
                        5 => 1,
                    ],
                    TEST_FILES_PATH . 'source_with_eval.php(5) : eval()\'d code' => [
                        1 => 1,
                    ],
                ],
            ));

        return $stub;
    }

    protected function getCoverageForClassWithAnonymousFunction(): CodeCoverage
    {
        return new CoverageFixtureProvider()->coverageForClassWithAnonymousFunction();
    }

    protected function getCoverageForClassesWithTraitsAndInheritance(): CodeCoverage
    {
        return new CoverageFixtureProvider()->coverageForClassesWithTraitsAndInheritance();
    }

    protected function getCoverageForClassWithOutsideFunction(): CodeCoverage
    {
        $filter = new Filter;
        $filter->includeFile(TEST_FILES_PATH . 'source_with_class_and_outside_function.php');

        $coverage = new CodeCoverage(
            $this->setUpXdebugStubForClassWithOutsideFunction(),
            $filter,
        );

        $coverage->start('ClassWithOutsideFunction', null, true);
        $coverage->stop();

        return $coverage;
    }

    protected function setUpXdebugStubForClassWithOutsideFunction(): Driver
    {
        $stub = $this->createStub(Driver::class);

        $stub
            ->method('stop')
            ->willReturn(RawCodeCoverageData::fromXdebugWithoutPathCoverage(
                [
                    TEST_FILES_PATH . 'source_with_class_and_outside_function.php' => [
                        6  => 1,
                        12 => 1,
                        13 => 1,
                        16 => -1,
                    ],
                ],
            ));

        return $stub;
    }

    protected function removeTemporaryFiles(): void
    {
        $tmpPath = TEST_FILES_PATH . 'tmp';

        if (!is_dir($tmpPath)) {
            return;
        }

        $tmpFilesIterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($tmpPath, RecursiveDirectoryIterator::SKIP_DOTS),
            RecursiveIteratorIterator::CHILD_FIRST,
        );

        foreach ($tmpFilesIterator as $path => $fileInfo) {
            /* @var \SplFileInfo $fileInfo */
            $pathname = $fileInfo->getPathname();
            $fileInfo->isDir() ? rmdir($pathname) : unlink($pathname);
        }
    }

    protected function getCoverageForFilesWithUncoveredIncluded(): CodeCoverage
    {
        $data = $this->getLineCoverageXdebugDataForBankAccount();

        $stub = $this->createStub(Driver::class);

        $stub
            ->method('stop')
            ->willReturn(...$data);

        $filter = new Filter;
        $filter->includeFile(TEST_FILES_PATH . 'BankAccount.php');
        $filter->includeFile(TEST_FILES_PATH . 'NamespacedBankAccount.php');

        $coverage = new CodeCoverage($stub, $filter);
        $coverage->includeUncoveredFiles();

        $coverage->start(
            'BankAccountTest::testBalanceIsInitiallyZero',
            null,
            true,
        );

        $coverage->stop(
            true,
            null,
            TargetCollection::fromArray([
                Target::forMethod(BankAccount::class, 'getBalance'),
            ]),
            time: 2.0,
        );

        $coverage->start(
            'BankAccountTest::testBalanceCannotBecomeNegative',
        );

        $coverage->stop(
            true,
            null,
            TargetCollection::fromArray([
                Target::forMethod(BankAccount::class, 'withdrawMoney'),
            ]),
            time: 2.1,
        );

        $coverage->start(
            'BankAccountTest::testBalanceCannotBecomeNegative2',
        );

        $coverage->stop(
            true,
            null,
            TargetCollection::fromArray([
                Target::forMethod(BankAccount::class, 'depositMoney'),
            ]),
            time: 2.2,
        );

        $coverage->start(
            'BankAccountTest::testDepositWithdrawMoney',
        );

        $coverage->stop(
            true,
            null,
            TargetCollection::fromArray([
                Target::forMethod(BankAccount::class, 'getBalance'),
                Target::forMethod(BankAccount::class, 'depositMoney'),
                Target::forMethod(BankAccount::class, 'withdrawMoney'),
            ]),
            time: 2.3,
        );

        return $coverage;
    }

    protected function getCoverageForFilesWithUncoveredExcluded(): CodeCoverage
    {
        $data = $this->getLineCoverageXdebugDataForBankAccount();

        $stub = $this->createStub(Driver::class);

        $stub
            ->method('stop')
            ->willReturn(...$data);

        $filter = new Filter;
        $filter->includeFile(TEST_FILES_PATH . 'BankAccount.php');
        $filter->includeFile(TEST_FILES_PATH . 'NamespacedBankAccount.php');

        $coverage = new CodeCoverage($stub, $filter);
        $coverage->excludeUncoveredFiles();

        $coverage->start(
            'BankAccountTest::testBalanceIsInitiallyZero',
            null,
            true,
        );

        $coverage->stop(
            true,
            null,
            TargetCollection::fromArray([
                Target::forMethod(BankAccount::class, 'getBalance'),
            ]),
            time: 2.4,
        );

        $coverage->start(
            'BankAccountTest::testBalanceCannotBecomeNegative',
        );

        $coverage->stop(
            true,
            null,
            TargetCollection::fromArray([
                Target::forMethod(BankAccount::class, 'withdrawMoney'),
            ]),
            time: 2.5,
        );

        $coverage->start(
            'BankAccountTest::testBalanceCannotBecomeNegative2',
        );

        $coverage->stop(
            true,
            null,
            TargetCollection::fromArray([
                Target::forMethod(BankAccount::class, 'depositMoney'),
            ]),
            time: 2.6,
        );

        $coverage->start(
            'BankAccountTest::testDepositWithdrawMoney',
        );

        $coverage->stop(
            true,
            null,
            TargetCollection::fromArray([
                Target::forMethod(BankAccount::class, 'getBalance'),
                Target::forMethod(BankAccount::class, 'depositMoney'),
                Target::forMethod(BankAccount::class, 'withdrawMoney'),
            ]),
            time: 2.7,
        );

        return $coverage;
    }

    /**
     * Builds a report whose tree contains a sub-directory node so that the
     * iteration in the report writers yields both Directory and File nodes.
     */
    protected function reportForNestedDirectories(): DirectoryNode
    {
        $analyser  = new FileAnalyser(new ParsingSourceAnalyser, false, false);
        $processed = new ProcessedCodeCoverageData;

        $files = [
            TEST_FILES_PATH . 'BankAccount.php',
            TEST_FILES_PATH . 'Target' . DIRECTORY_SEPARATOR . 'TargetClass.php',
        ];

        foreach ($files as $path) {
            $processed->initializeUnseenData(RawCodeCoverageData::fromUncoveredFile($path, $analyser));
        }

        return (new Builder($analyser))->build($processed, [], '');
    }

    /**
     * Builds a report that has branch coverage but also contains a file for
     * which no branch and path coverage data is available.
     */
    protected function reportWithFileWithoutBranchCoverageData(): DirectoryNode
    {
        $coverage = $this->getPathCoverageForBankAccount();

        $coverage->filter()->includeFile(TEST_FILES_PATH . 'Target' . DIRECTORY_SEPARATOR . 'TargetClass.php');
        $coverage->includeUncoveredFiles();

        return $coverage->getReport();
    }

    protected function coverageForBankAccountWithVariousTestSizesAndStatuses(): CodeCoverage
    {
        return new CoverageFixtureProvider()->coverageForBankAccountWithVariousTestSizesAndStatuses();
    }

    protected function pathCoverageForBankAccountWithVariousTestSizes(): CodeCoverage
    {
        return new CoverageFixtureProvider()->pathCoverageForBankAccountWithVariousTestSizes();
    }

    protected function pathCoverageForBankAccountWithPartialBranchAndPathCoverage(): CodeCoverage
    {
        return new CoverageFixtureProvider()->pathCoverageForBankAccountWithPartialBranchAndPathCoverage();
    }
}
