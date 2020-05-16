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

use SebastianBergmann\CodeCoverage\Driver\Driver;

abstract class TestCase extends \PHPUnit\Framework\TestCase
{
    protected static $TEST_TMP_PATH;

    public static function setUpBeforeClass(): void
    {
        self::$TEST_TMP_PATH = TEST_FILES_PATH . 'tmp';
    }

    protected function getXdebugDataForBankAccount()
    {
        return [
            RawCodeCoverageData::fromXdebugWithoutPathCoverage([
                TEST_FILES_PATH . 'BankAccount.php' => [
                    8  => 1,
                    9  => -2,
                    13 => -1,
                    14 => -1,
                    15 => -1,
                    16 => -1,
                    18 => -1,
                    22 => -1,
                    24 => -1,
                    25 => -2,
                    29 => -1,
                    31 => -1,
                    32 => -2,
                ],
            ]),
            RawCodeCoverageData::fromXdebugWithoutPathCoverage([
                TEST_FILES_PATH . 'BankAccount.php' => [
                    8  => 1,
                    13 => 1,
                    16 => 1,
                    29 => 1,
                ],
            ]),
            RawCodeCoverageData::fromXdebugWithoutPathCoverage([
                TEST_FILES_PATH . 'BankAccount.php' => [
                    8  => 1,
                    13 => 1,
                    16 => 1,
                    22 => 1,
                ],
            ]),
            RawCodeCoverageData::fromXdebugWithoutPathCoverage([
                TEST_FILES_PATH . 'BankAccount.php' => [
                    8  => 1,
                    13 => 1,
                    14 => 1,
                    15 => 1,
                    18 => 1,
                    22 => 1,
                    24 => 1,
                    29 => 1,
                    31 => 1,
                ],
            ]),
        ];
    }

    protected function getCoverageForBankAccount(): CodeCoverage
    {
        $data = $this->getXdebugDataForBankAccount();

        $stub = $this->createMock(Driver::class);

        $stub->expects($this->any())
            ->method('stop')
            ->will($this->onConsecutiveCalls(
                $data[0],
                $data[1],
                $data[2],
                $data[3]
            ));

        $filter = new Filter;
        $filter->addFileToWhitelist(TEST_FILES_PATH . 'BankAccount.php');

        $coverage = new CodeCoverage($stub, $filter);

        $coverage->start(
            new \BankAccountTest('testBalanceIsInitiallyZero'),
            true
        );

        $coverage->stop(
            true,
            [TEST_FILES_PATH . 'BankAccount.php' => \range(6, 9)]
        );

        $coverage->start(
            new \BankAccountTest('testBalanceCannotBecomeNegative')
        );

        $coverage->stop(
            true,
            [TEST_FILES_PATH . 'BankAccount.php' => \range(27, 32)]
        );

        $coverage->start(
            new \BankAccountTest('testBalanceCannotBecomeNegative2')
        );

        $coverage->stop(
            true,
            [TEST_FILES_PATH . 'BankAccount.php' => \range(20, 25)]
        );

        $coverage->start(
            new \BankAccountTest('testDepositWithdrawMoney')
        );

        $coverage->stop(
            true,
            [
                TEST_FILES_PATH . 'BankAccount.php' => \array_merge(
                    \range(6, 9),
                    \range(20, 25),
                    \range(27, 32)
                ),
            ]
        );

        return $coverage;
    }

    protected function getCoverageForBankAccountForFirstTwoTests(): CodeCoverage
    {
        $data = $this->getXdebugDataForBankAccount();

        $stub = $this->createMock(Driver::class);

        $stub->expects($this->any())
            ->method('stop')
            ->will($this->onConsecutiveCalls(
                $data[0],
                $data[1]
            ));

        $filter = new Filter;
        $filter->addFileToWhitelist(TEST_FILES_PATH . 'BankAccount.php');

        $coverage = new CodeCoverage($stub, $filter);

        $coverage->start(
            new \BankAccountTest('testBalanceIsInitiallyZero'),
            true
        );

        $coverage->stop(
            true,
            [TEST_FILES_PATH . 'BankAccount.php' => \range(6, 9)]
        );

        $coverage->start(
            new \BankAccountTest('testBalanceCannotBecomeNegative')
        );

        $coverage->stop(
            true,
            [TEST_FILES_PATH . 'BankAccount.php' => \range(27, 32)]
        );

        return $coverage;
    }

    protected function getCoverageForBankAccountForLastTwoTests(): CodeCoverage
    {
        $data = $this->getXdebugDataForBankAccount();

        $stub = $this->createMock(Driver::class);

        $stub->expects($this->any())
            ->method('stop')
            ->will($this->onConsecutiveCalls(
                $data[2],
                $data[3]
            ));

        $filter = new Filter;
        $filter->addFileToWhitelist(TEST_FILES_PATH . 'BankAccount.php');

        $coverage = new CodeCoverage($stub, $filter);

        $coverage->start(
            new \BankAccountTest('testBalanceCannotBecomeNegative2')
        );

        $coverage->stop(
            true,
            [TEST_FILES_PATH . 'BankAccount.php' => \range(20, 25)]
        );

        $coverage->start(
            new \BankAccountTest('testDepositWithdrawMoney')
        );

        $coverage->stop(
            true,
            [
                TEST_FILES_PATH . 'BankAccount.php' => \array_merge(
                    \range(6, 9),
                    \range(20, 25),
                    \range(27, 32)
                ),
            ]
        );

        return $coverage;
    }

    protected function getExpectedDataArrayForBankAccount(): array
    {
        return [
            TEST_FILES_PATH . 'BankAccount.php' => [
                8 => [
                    0 => 'BankAccountTest::testBalanceIsInitiallyZero',
                    1 => 'BankAccountTest::testDepositWithdrawMoney',
                ],
                9  => null,
                13 => [],
                14 => [],
                15 => [],
                16 => [],
                18 => [],
                22 => [
                    0 => 'BankAccountTest::testBalanceCannotBecomeNegative2',
                    1 => 'BankAccountTest::testDepositWithdrawMoney',
                ],
                24 => [
                    0 => 'BankAccountTest::testDepositWithdrawMoney',
                ],
                25 => null,
                29 => [
                    0 => 'BankAccountTest::testBalanceCannotBecomeNegative',
                    1 => 'BankAccountTest::testDepositWithdrawMoney',
                ],
                31 => [
                    0 => 'BankAccountTest::testDepositWithdrawMoney',
                ],
                32 => null,
            ],
        ];
    }

    protected function getExpectedDataArrayForBankAccountInReverseOrder(): array
    {
        return [
            TEST_FILES_PATH . 'BankAccount.php' => [
                8 => [
                    0 => 'BankAccountTest::testDepositWithdrawMoney',
                    1 => 'BankAccountTest::testBalanceIsInitiallyZero',
                ],
                9  => null,
                13 => [],
                14 => [],
                15 => [],
                16 => [],
                18 => [],
                22 => [
                    0 => 'BankAccountTest::testBalanceCannotBecomeNegative2',
                    1 => 'BankAccountTest::testDepositWithdrawMoney',
                ],
                24 => [
                    0 => 'BankAccountTest::testDepositWithdrawMoney',
                ],
                25 => null,
                29 => [
                    0 => 'BankAccountTest::testDepositWithdrawMoney',
                    1 => 'BankAccountTest::testBalanceCannotBecomeNegative',
                ],
                31 => [
                    0 => 'BankAccountTest::testDepositWithdrawMoney',
                ],
                32 => null,
            ],
        ];
    }

    protected function getCoverageForFileWithIgnoredLines(): CodeCoverage
    {
        $filter = new Filter;
        $filter->addFileToWhitelist(TEST_FILES_PATH . 'source_with_ignore.php');

        $coverage = new CodeCoverage(
            $this->setUpXdebugStubForFileWithIgnoredLines(),
            $filter
        );

        $coverage->start('FileWithIgnoredLines', true);
        $coverage->stop();

        return $coverage;
    }

    protected function setUpXdebugStubForFileWithIgnoredLines(): Driver
    {
        $stub = $this->createMock(Driver::class);

        $stub->expects($this->any())
            ->method('stop')
            ->will($this->returnValue(
                RawCodeCoverageData::fromXdebugWithoutPathCoverage(
                    [
                        TEST_FILES_PATH . 'source_with_ignore.php' => [
                            2 => 1,
                            4 => -1,
                            6 => -1,
                            7 => 1,
                        ],
                    ]
                )
            ));

        return $stub;
    }

    protected function getCoverageForClassWithAnonymousFunction(): CodeCoverage
    {
        $filter = new Filter;
        $filter->addFileToWhitelist(TEST_FILES_PATH . 'source_with_class_and_anonymous_function.php');

        $coverage = new CodeCoverage(
            $this->setUpXdebugStubForClassWithAnonymousFunction(),
            $filter
        );

        $coverage->start('ClassWithAnonymousFunction', true);
        $coverage->stop();

        return $coverage;
    }

    protected function setUpXdebugStubForClassWithAnonymousFunction(): Driver
    {
        $stub = $this->createMock(Driver::class);

        $stub->expects($this->any())
            ->method('stop')
            ->will($this->returnValue(
                RawCodeCoverageData::fromXdebugWithoutPathCoverage(
                    [
                        TEST_FILES_PATH . 'source_with_class_and_anonymous_function.php' => [
                            7  => 1,
                            9  => 1,
                            10 => -1,
                            11 => 1,
                            12 => 1,
                            13 => 1,
                            14 => 1,
                            17 => 1,
                            18 => 1,
                        ],
                    ]
                )
            ));

        return $stub;
    }

    protected function getCoverageForCrashParsing(): CodeCoverage
    {
        $filter = new Filter;
        $filter->addFileToWhitelist(TEST_FILES_PATH . 'Crash.php');

        // This is a file with invalid syntax, so it isn't executed.
        return new CodeCoverage(
            $this->setUpXdebugStubForCrashParsing(),
            $filter
        );
    }

    protected function setUpXdebugStubForCrashParsing(): Driver
    {
        $stub = $this->createMock(Driver::class);

        $stub->expects($this->any())
            ->method('stop')
            ->will($this->returnValue(RawCodeCoverageData::fromXdebugWithoutPathCoverage([])));

        return $stub;
    }

    protected function removeTemporaryFiles(): void
    {
        $tmpFilesIterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator(self::$TEST_TMP_PATH, \RecursiveDirectoryIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::CHILD_FIRST
        );

        foreach ($tmpFilesIterator as $path => $fileInfo) {
            /* @var \SplFileInfo $fileInfo */
            $pathname = $fileInfo->getPathname();
            $fileInfo->isDir() ? \rmdir($pathname) : \unlink($pathname);
        }
    }
}
