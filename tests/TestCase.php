<?php
/*
 * This file is part of the PHP_CodeCoverage package.
 *
 * (c) Sebastian Bergmann <sebastian@phpunit.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

if (!defined('TEST_FILES_PATH')) {
    define('TEST_FILES_PATH', dirname(__FILE__) . DIRECTORY_SEPARATOR . '_files' . DIRECTORY_SEPARATOR);
}

/**
 * Abstract base class for test case classes.
 *
 * @since Class available since Release 1.0.0
 */
abstract class PHP_CodeCoverage_TestCase extends PHPUnit_Framework_TestCase
{
    static protected $TEST_TMP_PATH;

    public static function setUpBeforeClass()
    {
        self::$TEST_TMP_PATH = TEST_FILES_PATH . 'tmp';
    }

    protected function getXdebugDataForBankAccount()
    {
        $bankAccountFunctions = [
            'BankAccount->depositMoney' => [
                'branches' => [
                    0 => [
                        'op_start'   => 0,
                        'op_end'     => 20,
                        'line_start' => 20,
                        'line_end'   => 25,
                        'hit'        => 0,
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
                        'hit' => 0,
                    ],
                ],
            ],
            'BankAccount->getBalance' => [
                'branches' => [
                    0 => [
                        'op_start'   => 0,
                        'op_end'     => 5,
                        'line_start' => 6,
                        'line_end'   => 9,
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
                        ],
                        'hit' => 1,
                    ],
                ],
            ],
            'BankAccount->withdrawMoney' => [
                'branches' => [
                    0 => [
                        'op_start'   => 0,
                        'op_end'     => 20,
                        'line_start' => 27,
                        'line_end'   => 32,
                        'hit'        => 0,
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
                        'hit' => 0,
                    ],
                ],
            ],
            'BankAccount->setBalance' => [
                'branches' => [
                    0 => [
                        'op_start'   => 0,
                        'op_end'     => 4,
                        'line_start' => 11,
                        'line_end'   => 13,
                        'hit'        => 0,
                        'out'        => [
                            0 => 5,
                            1 => 9,
                        ],
                        'out_hit' => [
                            0 => 0,
                            1 => 0,
                        ],
                    ],
                    5 => [
                        'op_start'   => 5,
                        'op_end'     => 8,
                        'line_start' => 14,
                        'line_end'   => 15,
                        'hit'        => 0,
                        'out'        => [
                            0 => 16,
                        ],
                        'out_hit' => [
                            0 => 0,
                        ],
                    ],
                    9 => [
                        'op_start'   => 9,
                        'op_end'     => 15,
                        'line_start' => 16,
                        'line_end'   => 16,
                        'hit'        => 0,
                        'out'        => [
                            0 => 2147483645,
                        ],
                        'out_hit' => [
                            0 => 0,
                        ],
                    ],
                    16 => [
                        'op_start'   => 16,
                        'op_end'     => 17,
                        'line_start' => 18,
                        'line_end'   => 18,
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
                            1 => 5,
                            2 => 16,
                        ],
                        'hit' => 0,
                    ],
                    1 => [
                        'path' => [
                            0 => 0,
                            1 => 9,
                        ],
                        'hit' => 0,
                    ],
                ],
            ],
        ];

        return [
            [
                TEST_FILES_PATH . 'BankAccount.php' => [
                    'lines' => [
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
                    'functions' => $bankAccountFunctions,
                ]
            ],
            [
                TEST_FILES_PATH . 'BankAccount.php' => [
                    'lines' => [
                        8  => 1,
                        13 => 1,
                        16 => 1,
                        29 => 1,
                    ],
                    'functions' => $bankAccountFunctions,
                ]
            ],
            [
                TEST_FILES_PATH . 'BankAccount.php' => [
                    'lines' => [
                        8  => 1,
                        13 => 1,
                        16 => 1,
                        22 => 1,
                    ],
                    'functions' => $bankAccountFunctions,
                ]
            ],
            [
                TEST_FILES_PATH . 'BankAccount.php' => [
                    'lines' => [
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
                    'functions' => $bankAccountFunctions,
                ]
            ]
        ];
    }

    protected function getCoverageForBankAccount()
    {
        $data = $this->getXdebugDataForBankAccount();
        require_once TEST_FILES_PATH . 'BankAccountTest.php';

        $stub = $this->getMock('PHP_CodeCoverage_Driver_Xdebug');
        $stub->expects($this->any())
            ->method('stop')
            ->will($this->onConsecutiveCalls(
                $data[0],
                $data[1],
                $data[2],
                $data[3]
            ));

        $filter = new PHP_CodeCoverage_Filter;
        $filter->addFileToWhitelist(TEST_FILES_PATH . 'BankAccount.php');

        $coverage = new PHP_CodeCoverage($stub, $filter);

        $coverage->start(
            new BankAccountTest('testBalanceIsInitiallyZero'),
            true
        );

        $coverage->stop(
            true,
            [TEST_FILES_PATH . 'BankAccount.php' => range(6, 9)]
        );

        $coverage->start(
            new BankAccountTest('testBalanceCannotBecomeNegative')
        );

        $coverage->stop(
            true,
            [TEST_FILES_PATH . 'BankAccount.php' => range(27, 32)]
        );

        $coverage->start(
            new BankAccountTest('testBalanceCannotBecomeNegative2')
        );

        $coverage->stop(
            true,
            [TEST_FILES_PATH . 'BankAccount.php' => range(20, 25)]
        );

        $coverage->start(
            new BankAccountTest('testDepositWithdrawMoney')
        );

        $coverage->stop(
            true,
            [
                TEST_FILES_PATH . 'BankAccount.php' => array_merge(
                    range(6, 9),
                    range(20, 25),
                    range(27, 32)
                )
            ]
        );

        return $coverage;
    }

    protected function getCoverageForBankAccountForFirstTwoTests()
    {
        $data = $this->getXdebugDataForBankAccount();

        $stub = $this->getMock('PHP_CodeCoverage_Driver_Xdebug');
        $stub->expects($this->any())
            ->method('stop')
            ->will($this->onConsecutiveCalls(
                $data[0],
                $data[1]
            ));

        $filter = new PHP_CodeCoverage_Filter;
        $filter->addFileToWhitelist(TEST_FILES_PATH . 'BankAccount.php');

        $coverage = new PHP_CodeCoverage($stub, $filter);

        $coverage->start(
            new BankAccountTest('testBalanceIsInitiallyZero'),
            true
        );

        $coverage->stop(
            true,
            [TEST_FILES_PATH . 'BankAccount.php' => range(6, 9)]
        );

        $coverage->start(
            new BankAccountTest('testBalanceCannotBecomeNegative')
        );

        $coverage->stop(
            true,
            [TEST_FILES_PATH . 'BankAccount.php' => range(27, 32)]
        );

        return $coverage;
    }

    protected function getCoverageForBankAccountForLastTwoTests()
    {
        $data = $this->getXdebugDataForBankAccount();

        $stub = $this->getMock('PHP_CodeCoverage_Driver_Xdebug');
        $stub->expects($this->any())
            ->method('stop')
            ->will($this->onConsecutiveCalls(
                $data[2],
                $data[3]
            ));

        $filter = new PHP_CodeCoverage_Filter;
        $filter->addFileToWhitelist(TEST_FILES_PATH . 'BankAccount.php');

        $coverage = new PHP_CodeCoverage($stub, $filter);

        $coverage->start(
            new BankAccountTest('testBalanceCannotBecomeNegative2')
        );

        $coverage->stop(
            true,
            [TEST_FILES_PATH . 'BankAccount.php' => range(20, 25)]
        );

        $coverage->start(
            new BankAccountTest('testDepositWithdrawMoney')
        );

        $coverage->stop(
            true,
            [
                TEST_FILES_PATH . 'BankAccount.php' => array_merge(
                    range(6, 9),
                    range(20, 25),
                    range(27, 32)
                )
            ]
        );

        return $coverage;
    }

    protected function getExpectedDataArrayForBankAccount()
    {
        return [
            TEST_FILES_PATH . 'BankAccount.php' => [
                'lines' => [
                    8 => [
                        'pathCovered' => true,
                        'tests'       => [
                            0 => 'BankAccountTest::testBalanceIsInitiallyZero',
                            1 => 'BankAccountTest::testDepositWithdrawMoney',
                        ],
                    ],
                    9  => null,
                    13 => [
                        'pathCovered' => false,
                        'tests'       => [],
                    ],
                    14 => [
                        'pathCovered' => false,
                        'tests'       => [],
                    ],
                    15 => [
                        'pathCovered' => false,
                        'tests'       => [],
                    ],
                    16 => [
                        'pathCovered' => false,
                        'tests'       => [],
                    ],
                    18 => [
                        'pathCovered' => false,
                        'tests'       => [],
                    ],
                    22 => [
                        'pathCovered' => false,
                        'tests'       => [
                            0 => 'BankAccountTest::testBalanceCannotBecomeNegative2',
                            1 => 'BankAccountTest::testDepositWithdrawMoney',
                        ],
                    ],
                    24 => [
                        'pathCovered' => false,
                        'tests'       => [
                            0 => 'BankAccountTest::testDepositWithdrawMoney',
                        ],
                    ],
                    25 => null,
                    29 => [
                        'pathCovered' => false,
                        'tests'       => [
                            0 => 'BankAccountTest::testBalanceCannotBecomeNegative',
                            1 => 'BankAccountTest::testDepositWithdrawMoney',
                        ],
                    ],
                    31 => [
                        'pathCovered' => false,
                        'tests'       => [
                            0 => 'BankAccountTest::testDepositWithdrawMoney',
                        ],
                    ],
                    32 => null,
                ],
                'branches' => [
                    'BankAccount->depositMoney' => [
                        0 => [
                            'line_start' => 20,
                            'line_end'   => 25,
                            'tests'      => [],
                            'hit'        => 0,
                        ],
                    ],
                    'BankAccount->getBalance' => [
                        0 => [
                            'line_start' => 6,
                            'line_end'   => 9,
                            'tests'      => [],
                            'hit'        => 1,
                        ],
                    ],
                    'BankAccount->withdrawMoney' => [
                        0 => [
                            'line_start' => 27,
                            'line_end'   => 32,
                            'tests'      => [],
                            'hit'        => 0,
                        ],
                    ],
                    'BankAccount->setBalance' => [
                        0 => [
                            'line_start' => 11,
                            'line_end'   => 13,
                            'tests'      => [],
                            'hit'        => 0,
                        ],
                        1 => [
                            'line_start' => 14,
                            'line_end'   => 15,
                            'tests'      => [],
                            'hit'        => 0,
                        ],
                        2 => [
                            'line_start' => 16,
                            'line_end'   => 16,
                            'tests'      => [],
                            'hit'        => 0,
                        ],
                        3 => [
                            'line_start' => 18,
                            'line_end'   => 18,
                            'tests'      => [],
                            'hit'        => 0,
                        ],
                    ],
                ],
                'paths' => [
                    'BankAccount->depositMoney' => [
                        0 => [
                            'path' => [
                                0 => 0,
                            ],
                            'hit' => 0,
                        ],
                    ],
                    'BankAccount->getBalance' => [
                        0 => [
                            'path' => [
                                0 => 0,
                            ],
                            'hit' => 1,
                        ],
                    ],
                    'BankAccount->withdrawMoney' => [
                        0 => [
                            'path' => [
                                0 => 0,
                            ],
                            'hit' => 0,
                        ],
                    ],
                    'BankAccount->setBalance' => [
                        0 => [
                            'path' => [
                                0 => 0,
                                1 => 5,
                                2 => 16,
                            ],
                            'hit' => 0,
                        ],
                        1 => [
                            'path' => [
                                0 => 0,
                                1 => 9,
                            ],
                            'hit' => 0,
                        ],
                    ],
                ],
            ]
        ];
    }

    protected function getCoverageForFileWithIgnoredLines()
    {
        $filter = new PHP_CodeCoverage_Filter;
        $filter->addFileToWhitelist(TEST_FILES_PATH . 'source_with_ignore.php');

        $coverage = new PHP_CodeCoverage(
            $this->setUpXdebugStubForFileWithIgnoredLines(),
            $filter
        );

        $coverage->start('FileWithIgnoredLines', true);
        $coverage->stop();

        return $coverage;
    }

    protected function setUpXdebugStubForFileWithIgnoredLines()
    {
        $stub = $this->getMock('PHP_CodeCoverage_Driver_Xdebug');
        $stub->expects($this->any())
            ->method('stop')
            ->will($this->returnValue(
                [
                    TEST_FILES_PATH . 'source_with_ignore.php' => [
                        'lines' => [
                            2 => 1,
                            4 => -1,
                            6 => -1,
                            7 => 1,
                        ],
                        'functions' => [
                            'Bar->foo' => [
                                'branches' => [
                                    0 => [
                                        'op_start'   => 0,
                                        'op_end'     => 2,
                                        'line_start' => 23,
                                        'line_end'   => 25,
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
                                        'line_start' => 13,
                                        'line_end'   => 15,
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
                                        'op_end'     => 5,
                                        'line_start' => 28,
                                        'line_end'   => 31,
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
                    ]
                ]
            ));

        return $stub;
    }

    protected function getCoverageForClassWithAnonymousFunction()
    {
        $filter = new PHP_CodeCoverage_Filter;
        $filter->addFileToWhitelist(TEST_FILES_PATH . 'source_with_class_and_anonymous_function.php');

        $coverage = new PHP_CodeCoverage(
            $this->setUpXdebugStubForClassWithAnonymousFunction(),
            $filter
        );

        $coverage->start('ClassWithAnonymousFunction', true);
        $coverage->stop();

        return $coverage;
    }

    protected function setUpXdebugStubForClassWithAnonymousFunction()
    {
        $stub = $this->getMock('PHP_CodeCoverage_Driver_Xdebug');
        $stub->expects($this->any())
            ->method('stop')
            ->will($this->returnValue(
                [
                    TEST_FILES_PATH . 'source_with_class_and_anonymous_function.php' => [
                        'lines' => [
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
                        'functions' => [
                            'CoveredClassWithAnonymousFunctionInStaticMethod->runAnonymous' => [
                                'branches' => [
                                    0 => [
                                        'op_start'   => 0,
                                        'op_end'     => 16,
                                        'line_start' => 5,
                                        'line_end'   => 18,
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
                            '{closure:' . TEST_FILES_PATH . 'source_with_class_and_anonymous_function.php:11-13}' => [
                                'branches' => [
                                    0 => [
                                        'op_start'   => 0,
                                        'op_end'     => 12,
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
                        ],
                    ]
                ]
            ));

        return $stub;
    }
}
