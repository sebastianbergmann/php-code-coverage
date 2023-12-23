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

use SebastianBergmann\CodeCoverage\Data\RawCodeCoverageData;
use function array_merge;
use function range;
use function rmdir;
use function unlink;
use BankAccountTest;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use SebastianBergmann\CodeCoverage\Driver\Driver;

abstract class TestCase extends \PHPUnit\Framework\TestCase
{
    protected static $TEST_TMP_PATH;

    public static function setUpBeforeClass(): void
    {
        self::$TEST_TMP_PATH = TEST_FILES_PATH . 'tmp';
    }

    protected function getLineCoverageXdebugDataForBankAccount()
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
                    33 => -2,
                    35 => 1,
                ],
            ]),
            RawCodeCoverageData::fromXdebugWithoutPathCoverage([
                TEST_FILES_PATH . 'BankAccount.php' => [
                    8  => 1,
                    9  => -2,
                    13 => 1,
                    14 => -1,
                    15 => -1,
                    16 => 1,
                    18 => -1,
                    29 => 1,
                    31 => -1,
                    32 => -2,
                    33 => -2,
                ],
            ]),
            RawCodeCoverageData::fromXdebugWithoutPathCoverage([
                TEST_FILES_PATH . 'BankAccount.php' => [
                    8  => 1,
                    9  => -2,
                    13 => 1,
                    14 => -1,
                    15 => -1,
                    16 => 1,
                    18 => -1,
                    22 => 1,
                    24 => -1,
                    25 => -2,
                ],
            ]),
            RawCodeCoverageData::fromXdebugWithoutPathCoverage([
                TEST_FILES_PATH . 'BankAccount.php' => [
                    8  => 1,
                    9  => -2,
                    13 => 1,
                    14 => 1,
                    15 => 1,
                    16 => -1,
                    18 => 1,
                    22 => 1,
                    24 => 1,
                    25 => -2,
                    29 => 1,
                    31 => 1,
                    32 => -2,
                    33 => -2,
                ],
            ]),
        ];
    }

    protected function getPathCoverageXdebugDataForBankAccount()
    {
        return [
            RawCodeCoverageData::fromXdebugWithPathCoverage([
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
                    'functions' => [
                        'BankAccount->depositMoney' => [
                            'branches' => [
                                0 => [
                                    'op_start'   => 0,
                                    'op_end'     => 14,
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
                                    'op_end'     => 14,
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
                        '{main}' => [
                            'branches' => [
                                0 => [
                                    'op_start'   => 0,
                                    'op_end'     => 1,
                                    'line_start' => 34,
                                    'line_end'   => 34,
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
                                    'line_end'   => 14,
                                    'hit'        => 0,
                                    'out'        => [
                                        0 => 13,
                                    ],
                                    'out_hit' => [
                                        0 => 0,
                                    ],
                                ],
                                9 => [
                                    'op_start'   => 9,
                                    'op_end'     => 12,
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
                                13 => [
                                    'op_start'   => 13,
                                    'op_end'     => 14,
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
                                        2 => 13,
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
                    ],
                ],
            ]),
            RawCodeCoverageData::fromXdebugWithPathCoverage([
                TEST_FILES_PATH . 'BankAccount.php' => [
                    'lines' => [
                        8  => 1,
                        9  => -2,
                        13 => 1,
                        14 => -1,
                        15 => -1,
                        16 => 1,
                        18 => -1,
                        29 => 1,
                        31 => -1,
                        32 => -2,
                    ],
                    'functions' => [
                        'BankAccount->depositMoney' => [
                            'branches' => [
                                0 => [
                                    'op_start'   => 0,
                                    'op_end'     => 14,
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
                                    'op_end'     => 14,
                                    'line_start' => 27,
                                    'line_end'   => 32,
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
                        '{main}' => [
                            'branches' => [
                                0 => [
                                    'op_start'   => 0,
                                    'op_end'     => 1,
                                    'line_start' => 34,
                                    'line_end'   => 34,
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
                        'BankAccount->setBalance' => [
                            'branches' => [
                                0 => [
                                    'op_start'   => 0,
                                    'op_end'     => 4,
                                    'line_start' => 11,
                                    'line_end'   => 13,
                                    'hit'        => 1,
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
                                    'line_end'   => 14,
                                    'hit'        => 0,
                                    'out'        => [
                                        0 => 13,
                                    ],
                                    'out_hit' => [
                                        0 => 0,
                                    ],
                                ],
                                9 => [
                                    'op_start'   => 9,
                                    'op_end'     => 12,
                                    'line_start' => 16,
                                    'line_end'   => 16,
                                    'hit'        => 1,
                                    'out'        => [
                                        0 => 2147483645,
                                    ],
                                    'out_hit' => [
                                        0 => 0,
                                    ],
                                ],
                                13 => [
                                    'op_start'   => 13,
                                    'op_end'     => 14,
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
                                        2 => 13,
                                    ],
                                    'hit' => 0,
                                ],
                                1 => [
                                    'path' => [
                                        0 => 0,
                                        1 => 9,
                                    ],
                                    'hit' => 1,
                                ],
                            ],
                        ],
                    ],
                ],
            ]),
            RawCodeCoverageData::fromXdebugWithPathCoverage([
                TEST_FILES_PATH . 'BankAccount.php' => [
                    'lines' => [
                        8  => 1,
                        9  => -2,
                        13 => 1,
                        14 => -1,
                        15 => -1,
                        16 => 1,
                        18 => -1,
                        22 => 1,
                        24 => -1,
                        25 => -2,
                    ],
                    'functions' => [
                        'BankAccount->depositMoney' => [
                            'branches' => [
                                0 => [
                                    'op_start'   => 0,
                                    'op_end'     => 14,
                                    'line_start' => 20,
                                    'line_end'   => 25,
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
                                    'op_end'     => 14,
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
                        '{main}' => [
                            'branches' => [
                                0 => [
                                    'op_start'   => 0,
                                    'op_end'     => 1,
                                    'line_start' => 34,
                                    'line_end'   => 34,
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
                        'BankAccount->setBalance' => [
                            'branches' => [
                                0 => [
                                    'op_start'   => 0,
                                    'op_end'     => 4,
                                    'line_start' => 11,
                                    'line_end'   => 13,
                                    'hit'        => 1,
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
                                    'line_end'   => 14,
                                    'hit'        => 0,
                                    'out'        => [
                                        0 => 13,
                                    ],
                                    'out_hit' => [
                                        0 => 0,
                                    ],
                                ],
                                9 => [
                                    'op_start'   => 9,
                                    'op_end'     => 12,
                                    'line_start' => 16,
                                    'line_end'   => 16,
                                    'hit'        => 1,
                                    'out'        => [
                                        0 => 2147483645,
                                    ],
                                    'out_hit' => [
                                        0 => 0,
                                    ],
                                ],
                                13 => [
                                    'op_start'   => 13,
                                    'op_end'     => 14,
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
                                        2 => 13,
                                    ],
                                    'hit' => 0,
                                ],
                                1 => [
                                    'path' => [
                                        0 => 0,
                                        1 => 9,
                                    ],
                                    'hit' => 1,
                                ],
                            ],
                        ],
                    ],
                ],
            ]),
            RawCodeCoverageData::fromXdebugWithPathCoverage([
                TEST_FILES_PATH . 'BankAccount.php' => [
                    'lines' => [
                        8  => 1,
                        9  => -2,
                        13 => 1,
                        14 => 1,
                        15 => 1,
                        16 => -1,
                        18 => 1,
                        22 => 1,
                        24 => 1,
                        25 => -2,
                        29 => 1,
                        31 => 1,
                        32 => -2,
                    ],
                    'functions' => [
                        'BankAccount->depositMoney' => [
                            'branches' => [
                                0 => [
                                    'op_start'   => 0,
                                    'op_end'     => 14,
                                    'line_start' => 20,
                                    'line_end'   => 25,
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
                                    'op_end'     => 14,
                                    'line_start' => 27,
                                    'line_end'   => 32,
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
                        '{main}' => [
                            'branches' => [
                                0 => [
                                    'op_start'   => 0,
                                    'op_end'     => 1,
                                    'line_start' => 34,
                                    'line_end'   => 34,
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
                        'BankAccount->setBalance' => [
                            'branches' => [
                                0 => [
                                    'op_start'   => 0,
                                    'op_end'     => 4,
                                    'line_start' => 11,
                                    'line_end'   => 13,
                                    'hit'        => 1,
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
                                    'line_end'   => 14,
                                    'hit'        => 1,
                                    'out'        => [
                                        0 => 13,
                                    ],
                                    'out_hit' => [
                                        0 => 0,
                                    ],
                                ],
                                9 => [
                                    'op_start'   => 9,
                                    'op_end'     => 12,
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
                                13 => [
                                    'op_start'   => 13,
                                    'op_end'     => 14,
                                    'line_start' => 18,
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
                                        1 => 5,
                                        2 => 13,
                                    ],
                                    'hit' => 1,
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
                    ],
                ],
            ]),
        ];
    }

    protected function getPathCoverageXdebugDataForSourceWithoutNamespace()
    {
        return [
            RawCodeCoverageData::fromXdebugWithPathCoverage(
                [
                    TEST_FILES_PATH . 'source_without_namespace.php' => [
                        'lines' => [
                            14 => -1,
                            15 => -1,
                            16 => -1,
                            17 => -1,
                            18 => -1,
                            19 => 1,
                        ],
                        'functions' => [
                            '{closure:' . TEST_FILES_PATH . 'source_without_namespace.php:14-14}' => [
                                'branches' => [
                                    0 => [
                                        'op_start'   => 0,
                                        'op_end'     => 2,
                                        'line_start' => 14,
                                        'line_end'   => 14,
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
                            'foo' => [
                                'branches' => [
                                    0 => [
                                        'op_start'   => 0,
                                        'op_end'     => 6,
                                        'line_start' => 12,
                                        'line_end'   => 15,
                                        'hit'        => 0,
                                        'out'        => [
                                            0 => 7,
                                            1 => 9,
                                        ],
                                        'out_hit' => [
                                            0 => 0,
                                            1 => 0,
                                        ],
                                    ],
                                    7 => [
                                        'op_start'   => 7,
                                        'op_end'     => 8,
                                        'line_start' => 15,
                                        'line_end'   => 15,
                                        'hit'        => 0,
                                        'out'        => [
                                            0 => 10,
                                        ],
                                        'out_hit' => [
                                            0 => 0,
                                        ],
                                    ],
                                    9 => [
                                        'op_start'   => 9,
                                        'op_end'     => 9,
                                        'line_start' => 15,
                                        'line_end'   => 15,
                                        'hit'        => 0,
                                        'out'        => [
                                            0 => 10,
                                        ],
                                        'out_hit' => [
                                            0 => 0,
                                        ],
                                    ],
                                    10 => [
                                        'op_start'   => 10,
                                        'op_end'     => 18,
                                        'line_start' => 15,
                                        'line_end'   => 18,
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
                                            1 => 7,
                                            2 => 10,
                                        ],
                                        'hit' => 0,
                                    ],
                                    1 => [
                                        'path' => [
                                            0 => 0,
                                            1 => 9,
                                            2 => 10,
                                        ],
                                        'hit' => 0,
                                    ],
                                ],
                            ],
                            '{main}' => [
                                'branches' => [
                                    0 => [
                                        'op_start'   => 0,
                                        'op_end'     => 0,
                                        'line_start' => 19,
                                        'line_end'   => 19,
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
                ]
            ),
        ];
    }

    protected function getLineCoverageForBankAccount(): CodeCoverage
    {
        $data = $this->getLineCoverageXdebugDataForBankAccount();

        $stub = $this->createStub(Driver::class);

        $stub->method('stop')
             ->willReturn(...$data);

        $filter = new Filter;
        $filter->includeFile(TEST_FILES_PATH . 'BankAccount.php');

        $coverage = new CodeCoverage($stub, $filter);

        $coverage->start(
            'BankAccountTest::testBalanceIsInitiallyZero',
            null,
            true
        );

        $coverage->stop(
            true,
            null,
            [TEST_FILES_PATH . 'BankAccount.php' => range(6, 9)]
        );

        $coverage->start(
            'BankAccountTest::testBalanceCannotBecomeNegative'
        );

        $coverage->stop(
            true,
            null,
            [TEST_FILES_PATH . 'BankAccount.php' => range(27, 32)]
        );

        $coverage->start(
            'BankAccountTest::testBalanceCannotBecomeNegative2'
        );

        $coverage->stop(
            true,
            null,
            [TEST_FILES_PATH . 'BankAccount.php' => range(20, 25)]
        );

        $coverage->start(
            'BankAccountTest::testDepositWithdrawMoney'
        );

        $coverage->stop(
            true,
            null,
            [
                TEST_FILES_PATH . 'BankAccount.php' => array_merge(
                    range(6, 9),
                    range(20, 25),
                    range(27, 32)
                ),
            ]
        );

        return $coverage;
    }

    protected function getPathCoverageForBankAccount(): CodeCoverage
    {
        $data = $this->getPathCoverageXdebugDataForBankAccount();

        $stub = $this->createStub(Driver::class);

        $stub->method('collectsBranchAndPathCoverage')->willReturn(true);

        $stub->method('stop')
             ->willReturn(...$data);

        $filter = new Filter;
        $filter->includeFile(TEST_FILES_PATH . 'BankAccount.php');

        $coverage = new CodeCoverage($stub, $filter);

        $coverage->start(
            'BankAccountTest::testBalanceIsInitiallyZero',
            null,
            true
        );

        $coverage->stop(
            true,
            null,
            [TEST_FILES_PATH . 'BankAccount.php' => range(6, 9)]
        );

        $coverage->start(
            'BankAccountTest::testBalanceCannotBecomeNegative'
        );

        $coverage->stop(
            true,
            null,
            [TEST_FILES_PATH . 'BankAccount.php' => range(27, 32)]
        );

        $coverage->start(
            'BankAccountTest::testBalanceCannotBecomeNegative2'
        );

        $coverage->stop(
            true,
            null,
            [TEST_FILES_PATH . 'BankAccount.php' => range(20, 25)]
        );

        $coverage->start(
            'BankAccountTest::testDepositWithdrawMoney'
        );

        $coverage->stop(
            true,
            null,
            [
                TEST_FILES_PATH . 'BankAccount.php' => array_merge(
                    range(6, 9),
                    range(20, 25),
                    range(27, 32)
                ),
            ]
        );

        return $coverage;
    }

    protected function getPathCoverageForSourceWithoutNamespace(): CodeCoverage
    {
        $data = $this->getPathCoverageXdebugDataForSourceWithoutNamespace();

        $stub = $this->createStub(Driver::class);

        $stub->method('collectsBranchAndPathCoverage')->willReturn(true);

        $stub->method('stop')
            ->willReturn(...$data);

        $filter = new Filter;
        $filter->includeFile(TEST_FILES_PATH . 'source_without_namespace.php');

        $coverage = new CodeCoverage($stub, $filter);

        $coverage->start(
            'faketest',
            null,
            true
        );

        $coverage->stop();

        return $coverage;
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

        $stub->method('stop')
             ->willReturn(...$data);

        $filter = new Filter;
        $filter->includeFile(TEST_FILES_PATH . 'NamespacedBankAccount.php');

        $coverage = new CodeCoverage($stub, $filter);

        $coverage->start(
            'BankAccountTest::testBalanceIsInitiallyZero',
            null,
            true
        );

        $coverage->stop(
            true,
            null,
            [TEST_FILES_PATH . 'NamespacedBankAccount.php' => range(11, 14)]
        );

        $coverage->start(
            'BankAccountTest::testBalanceCannotBecomeNegative'
        );

        $coverage->stop(
            true,
            null,
            [TEST_FILES_PATH . 'NamespacedBankAccount.php' => range(32, 37)]
        );

        $coverage->start(
            'BankAccountTest::testBalanceCannotBecomeNegative2'
        );

        $coverage->stop(
            true,
            null,
            [TEST_FILES_PATH . 'NamespacedBankAccount.php' => range(25, 30)]
        );

        $coverage->start(
            'BankAccountTest::testDepositWithdrawMoney'
        );

        $coverage->stop(
            true,
            null,
            [
                TEST_FILES_PATH . 'NamespacedBankAccount.php' => array_merge(
                    range(11, 14),
                    range(25, 30),
                    range(32, 37)
                ),
            ]
        );

        return $coverage;
    }

    protected function getLineCoverageForBankAccountForFirstTwoTests(): CodeCoverage
    {
        $data = $this->getLineCoverageXdebugDataForBankAccount();

        $stub = $this->createStub(Driver::class);

        $stub->method('stop')
             ->willReturn(...$data);

        $filter = new Filter;
        $filter->includeFile(TEST_FILES_PATH . 'BankAccount.php');

        $coverage = new CodeCoverage($stub, $filter);

        $coverage->start(
            'BankAccountTest::testBalanceIsInitiallyZero',
            null,
            true
        );

        $coverage->stop(
            true,
            null,
            [TEST_FILES_PATH . 'BankAccount.php' => range(6, 9)]
        );

        $coverage->start(
            'BankAccountTest::testBalanceCannotBecomeNegative'
        );

        $coverage->stop(
            true,
            null,
            [TEST_FILES_PATH . 'BankAccount.php' => range(27, 32)]
        );

        return $coverage;
    }

    protected function getLineCoverageForBankAccountForLastTwoTests(): CodeCoverage
    {
        $data = $this->getLineCoverageXdebugDataForBankAccount();

        $stub = $this->createStub(Driver::class);

        $stub->method('stop')
             ->willReturn($data[2], $data[3]);

        $filter = new Filter;
        $filter->includeFile(TEST_FILES_PATH . 'BankAccount.php');

        $coverage = new CodeCoverage($stub, $filter);

        $coverage->start(
            'BankAccountTest::testBalanceCannotBecomeNegative2'
        );

        $coverage->stop(
            true,
            null,
            [TEST_FILES_PATH . 'BankAccount.php' => range(20, 25)]
        );

        $coverage->start(
            'BankAccountTest::testDepositWithdrawMoney'
        );

        $coverage->stop(
            true,
            null,
            [
                TEST_FILES_PATH . 'BankAccount.php' => array_merge(
                    range(6, 9),
                    range(20, 25),
                    range(27, 32)
                ),
            ]
        );

        return $coverage;
    }

    protected function getExpectedLineCoverageDataArrayForBankAccount(): array
    {
        return [
            TEST_FILES_PATH . 'BankAccount.php' => [
                8 => [
                    0 => 'BankAccountTest::testBalanceIsInitiallyZero',
                    1 => 'BankAccountTest::testDepositWithdrawMoney',
                ],
                13 => [],
                14 => [],
                16 => [],
                22 => [
                    0 => 'BankAccountTest::testBalanceCannotBecomeNegative2',
                    1 => 'BankAccountTest::testDepositWithdrawMoney',
                ],
                24 => [
                    0 => 'BankAccountTest::testDepositWithdrawMoney',
                ],
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

    protected function getExpectedLineCoverageDataArrayForBankAccountInReverseOrder(): array
    {
        return [
            TEST_FILES_PATH . 'BankAccount.php' => [
                8 => [
                    0 => 'BankAccountTest::testDepositWithdrawMoney',
                    1 => 'BankAccountTest::testBalanceIsInitiallyZero',
                ],
                13 => [],
                14 => [],
                16 => [],
                22 => [
                    0 => 'BankAccountTest::testBalanceCannotBecomeNegative2',
                    1 => 'BankAccountTest::testDepositWithdrawMoney',
                ],
                24 => [
                    0 => 'BankAccountTest::testDepositWithdrawMoney',
                ],
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

    protected function getPathCoverageForBankAccountForFirstTwoTests(): CodeCoverage
    {
        $data = $this->getPathCoverageXdebugDataForBankAccount();

        $stub = $this->createStub(Driver::class);

        $stub->method('stop')
             ->willReturn(...$data);

        $filter = new Filter;
        $filter->includeFile(TEST_FILES_PATH . 'BankAccount.php');

        $coverage = new CodeCoverage($stub, $filter);

        $coverage->start(
            'BankAccountTest::testBalanceIsInitiallyZero',
            null,
            true
        );

        $coverage->stop(
            true,
            null,
            [TEST_FILES_PATH . 'BankAccount.php' => range(6, 9)]
        );

        $coverage->start(
            'BankAccountTest::testBalanceCannotBecomeNegative'
        );

        $coverage->stop(
            true,
            null,
            [TEST_FILES_PATH . 'BankAccount.php' => range(27, 32)]
        );

        return $coverage;
    }

    protected function getPathCoverageForBankAccountForLastTwoTests(): CodeCoverage
    {
        $data = $this->getPathCoverageXdebugDataForBankAccount();

        $stub = $this->createStub(Driver::class);

        $stub->method('stop')
             ->willReturn($data[2], $data[3]);

        $filter = new Filter;
        $filter->includeFile(TEST_FILES_PATH . 'BankAccount.php');

        $coverage = new CodeCoverage($stub, $filter);

        $coverage->start(
            'BankAccountTest::testBalanceCannotBecomeNegative2'
        );

        $coverage->stop(
            true,
            null,
            [TEST_FILES_PATH . 'BankAccount.php' => range(20, 25)]
        );

        $coverage->start(
            'BankAccountTest::testDepositWithdrawMoney'
        );

        $coverage->stop(
            true,
            null,
            [
                TEST_FILES_PATH . 'BankAccount.php' => array_merge(
                    range(6, 9),
                    range(20, 25),
                    range(27, 32)
                ),
            ]
        );

        return $coverage;
    }

    protected function getExpectedPathCoverageDataArrayForBankAccount(): array
    {
        return [
            TEST_FILES_PATH . 'BankAccount.php' => [
                'BankAccount->depositMoney' => [
                    'branches' => [
                        0 => [
                            'op_start'   => 0,
                            'op_end'     => 14,
                            'line_start' => 20,
                            'line_end'   => 25,
                            'hit'        => [
                                0 => 'BankAccountTest::testBalanceCannotBecomeNegative2',
                                1 => 'BankAccountTest::testDepositWithdrawMoney',
                            ],
                            'out' => [
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
                            'hit' => [
                                0 => 'BankAccountTest::testBalanceCannotBecomeNegative2',
                                1 => 'BankAccountTest::testDepositWithdrawMoney',
                            ],
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
                            'hit'        => [
                                0 => 'BankAccountTest::testBalanceIsInitiallyZero',
                                1 => 'BankAccountTest::testDepositWithdrawMoney',
                            ],
                            'out' => [
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
                            'hit' => [
                                0 => 'BankAccountTest::testBalanceIsInitiallyZero',
                                1 => 'BankAccountTest::testDepositWithdrawMoney',
                            ],
                        ],
                    ],
                ],
                'BankAccount->withdrawMoney' => [
                    'branches' => [
                        0 => [
                            'op_start'   => 0,
                            'op_end'     => 14,
                            'line_start' => 27,
                            'line_end'   => 32,
                            'hit'        => [
                                0 => 'BankAccountTest::testBalanceCannotBecomeNegative',
                                1 => 'BankAccountTest::testDepositWithdrawMoney',
                            ],
                            'out' => [
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
                            'hit' => [
                                0 => 'BankAccountTest::testBalanceCannotBecomeNegative',
                                1 => 'BankAccountTest::testDepositWithdrawMoney',
                            ],
                        ],
                    ],
                ],
                '{main}' => [
                    'branches' => [
                        0 => [
                            'op_start'   => 0,
                            'op_end'     => 1,
                            'line_start' => 34,
                            'line_end'   => 34,
                            'hit'        => [
                            ],
                            'out' => [
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
                            'hit' => [
                            ],
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
                            'hit'        => [
                            ],
                            'out' => [
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
                            'line_end'   => 14,
                            'hit'        => [
                            ],
                            'out' => [
                                0 => 13,
                            ],
                            'out_hit' => [
                                0 => 0,
                            ],
                        ],
                        9 => [
                            'op_start'   => 9,
                            'op_end'     => 12,
                            'line_start' => 16,
                            'line_end'   => 16,
                            'hit'        => [
                            ],
                            'out' => [
                                0 => 2147483645,
                            ],
                            'out_hit' => [
                                0 => 0,
                            ],
                        ],
                        13 => [
                            'op_start'   => 13,
                            'op_end'     => 14,
                            'line_start' => 18,
                            'line_end'   => 18,
                            'hit'        => [
                            ],
                            'out' => [
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
                                2 => 13,
                            ],
                            'hit' => [
                            ],
                        ],
                        1 => [
                            'path' => [
                                0 => 0,
                                1 => 9,
                            ],
                            'hit' => [
                            ],
                        ],
                    ],
                ],
            ],
        ];
    }

    protected function getCoverageForFileWithIgnoredLines(): CodeCoverage
    {
        $filter = new Filter;
        $filter->includeFile(TEST_FILES_PATH . 'source_with_ignore.php');

        $coverage = new CodeCoverage(
            $this->setUpXdebugStubForFileWithIgnoredLines(),
            $filter
        );

        $coverage->start('FileWithIgnoredLines', null, true);
        $coverage->stop();

        return $coverage;
    }

    protected function setUpXdebugStubForFileWithIgnoredLines(): Driver
    {
        $stub = $this->createStub(Driver::class);

        $stub->method('stop')
             ->willReturn(RawCodeCoverageData::fromXdebugWithoutPathCoverage(
                 [
                     TEST_FILES_PATH . 'source_with_ignore.php' => [
                         2 => 1,
                         4 => -1,
                         6 => -1,
                     ],
                 ]
             ));

        return $stub;
    }

    protected function getLineCoverageForFileWithEval(): CodeCoverage
    {
        $filter = new Filter;
        $filter->includeFile(TEST_FILES_PATH . 'source_with_eval.php');

        $coverage = new CodeCoverage(
            $this->setUpXdebugStubForFileWithEval(),
            $filter
        );

        $coverage->start('FileWithEval', null, true);
        $coverage->stop();

        return $coverage;
    }

    protected function setUpXdebugStubForFileWithEval(): Driver
    {
        $stub = $this->createStub(Driver::class);

        $stub->method('stop')
            ->willReturn(RawCodeCoverageData::fromXdebugWithoutPathCoverage(
                [
                    TEST_FILES_PATH . 'source_with_eval.php' => [
                        3 => 1,
                        5 => 1,
                    ],
                    TEST_FILES_PATH . 'source_with_eval.php(5) : eval()\'d code' => [
                        1 => 1,
                    ],
                ]
            ));

        return $stub;
    }

    protected function getCoverageForClassWithAnonymousFunction(): CodeCoverage
    {
        $filter = new Filter;
        $filter->includeFile(TEST_FILES_PATH . 'source_with_class_and_anonymous_function.php');

        $coverage = new CodeCoverage(
            $this->setUpXdebugStubForClassWithAnonymousFunction(),
            $filter
        );

        $coverage->start('ClassWithAnonymousFunction', null, true);
        $coverage->stop();

        return $coverage;
    }

    protected function setUpXdebugStubForClassWithAnonymousFunction(): Driver
    {
        $stub = $this->createStub(Driver::class);

        $stub->method('stop')
             ->willReturn(RawCodeCoverageData::fromXdebugWithoutPathCoverage(
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
             ));

        return $stub;
    }

    protected function getCoverageForClassWithOutsideFunction(): CodeCoverage
    {
        $filter = new Filter;
        $filter->includeFile(TEST_FILES_PATH . 'source_with_class_and_outside_function.php');

        $coverage = new CodeCoverage(
            $this->setUpXdebugStubForClassWithOutsideFunction(),
            $filter
        );

        $coverage->start('ClassWithOutsideFunction', null, true);
        $coverage->stop();

        return $coverage;
    }

    protected function setUpXdebugStubForClassWithOutsideFunction(): Driver
    {
        $stub = $this->createStub(Driver::class);

        $stub->method('stop')
            ->willReturn(RawCodeCoverageData::fromXdebugWithoutPathCoverage(
                [
                    TEST_FILES_PATH . 'source_with_class_and_outside_function.php' => [
                        6  => 1,
                        12 => 1,
                        13 => 1,
                        16 => -1,
                    ],
                ]
            ));

        return $stub;
    }

    protected function removeTemporaryFiles(): void
    {
        $tmpFilesIterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator(self::$TEST_TMP_PATH, RecursiveDirectoryIterator::SKIP_DOTS),
            RecursiveIteratorIterator::CHILD_FIRST
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

        $stub->method('stop')
            ->willReturn(...$data);

        $filter = new Filter;
        $filter->includeFile(TEST_FILES_PATH . 'BankAccount.php');
        $filter->includeFile(TEST_FILES_PATH . 'NamespacedBankAccount.php');

        $coverage = new CodeCoverage($stub, $filter);
        $coverage->includeUncoveredFiles();

        $coverage->start(
            'BankAccountTest::testBalanceIsInitiallyZero',
            null,
            true
        );

        $coverage->stop(
            true,
            null,
            [TEST_FILES_PATH . 'BankAccount.php' => range(6, 9)]
        );

        $coverage->start(
            'BankAccountTest::testBalanceCannotBecomeNegative'
        );

        $coverage->stop(
            true,
            null,
            [TEST_FILES_PATH . 'BankAccount.php' => range(27, 32)]
        );

        $coverage->start(
            'BankAccountTest::testBalanceCannotBecomeNegative2'
        );

        $coverage->stop(
            true,
            null,
            [TEST_FILES_PATH . 'BankAccount.php' => range(20, 25)]
        );

        $coverage->start(
            'BankAccountTest::testDepositWithdrawMoney'
        );

        $coverage->stop(
            true,
            null,
            [
                TEST_FILES_PATH . 'BankAccount.php' => array_merge(
                    range(6, 9),
                    range(20, 25),
                    range(27, 32)
                ),
            ]
        );

        return $coverage;
    }

    protected function getCoverageForFilesWithUncoveredExcluded(): CodeCoverage
    {
        $data = $this->getLineCoverageXdebugDataForBankAccount();

        $stub = $this->createStub(Driver::class);

        $stub->method('stop')
            ->willReturn(...$data);

        $filter = new Filter;
        $filter->includeFile(TEST_FILES_PATH . 'BankAccount.php');
        $filter->includeFile(TEST_FILES_PATH . 'NamespacedBankAccount.php');

        $coverage = new CodeCoverage($stub, $filter);
        $coverage->excludeUncoveredFiles();

        $coverage->start(
            'BankAccountTest::testBalanceIsInitiallyZero',
            null,
            true
        );

        $coverage->stop(
            true,
            null,
            [TEST_FILES_PATH . 'BankAccount.php' => range(6, 9)]
        );

        $coverage->start(
            'BankAccountTest::testBalanceCannotBecomeNegative'
        );

        $coverage->stop(
            true,
            null,
            [TEST_FILES_PATH . 'BankAccount.php' => range(27, 32)]
        );

        $coverage->start(
            'BankAccountTest::testBalanceCannotBecomeNegative2'
        );

        $coverage->stop(
            true,
            null,
            [TEST_FILES_PATH . 'BankAccount.php' => range(20, 25)]
        );

        $coverage->start(
            'BankAccountTest::testDepositWithdrawMoney'
        );

        $coverage->stop(
            true,
            null,
            [
                TEST_FILES_PATH . 'BankAccount.php' => array_merge(
                    range(6, 9),
                    range(20, 25),
                    range(27, 32)
                ),
            ]
        );

        return $coverage;
    }
}
