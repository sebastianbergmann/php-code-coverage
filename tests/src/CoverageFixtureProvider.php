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
use BankAccount;
use SebastianBergmann\CodeCoverage\Data\RawCodeCoverageData;
use SebastianBergmann\CodeCoverage\Driver\Driver;
use SebastianBergmann\CodeCoverage\Test\Target\Target;
use SebastianBergmann\CodeCoverage\Test\Target\TargetCollection;
use SebastianBergmann\CodeCoverage\Test\TestSize;
use SebastianBergmann\CodeCoverage\Test\TestStatus;

/**
 * Provides the code coverage fixtures from which the expected report outputs
 * in tests/_files are generated.
 */
final readonly class CoverageFixtureProvider
{
    /**
     * @return list<RawCodeCoverageData>
     */
    public function lineCoverageXdebugDataForBankAccount(): array
    {
        return [
            RawCodeCoverageData::fromLineCoverage([
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
            RawCodeCoverageData::fromLineCoverage([
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
            RawCodeCoverageData::fromLineCoverage([
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
            RawCodeCoverageData::fromLineCoverage([
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

    /**
     * @return list<RawCodeCoverageData>
     */
    public function pathCoverageXdebugDataForBankAccount(): array
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

    /**
     * @return list<RawCodeCoverageData>
     */
    public function pathCoverageXdebugDataForSourceWithoutNamespace(): array
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
                ],
            ),
        ];
    }

    public function lineCoverageForBankAccount(): CodeCoverage
    {
        $data = $this->lineCoverageXdebugDataForBankAccount();

        $driver = new FakeDriver(...$data);

        $filter = new Filter;
        $filter->includeFile(TEST_FILES_PATH . 'BankAccount.php');

        $coverage = new CodeCoverage($driver, $filter);

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
            time: 0.1,
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
            time: 0.2,
        );

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
            time: 0.3,
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
            time: 0.4,
        );

        return $coverage;
    }

    /**
     * Same tests as lineCoverageForBankAccount(), but collected by a driver that
     * counts how often a line was executed.
     */
    public function lineCoverageWithHitCountsForBankAccount(): CodeCoverage
    {
        $data = $this->lineCoverageXdebugDataForBankAccount();

        // getBalance() is called twice by the first test ...
        $data[0] = RawCodeCoverageData::fromLineCoverage([
            TEST_FILES_PATH . 'BankAccount.php' => [
                8  => 2,
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
        ]);

        // ... and three times by the last test, which also calls depositMoney() twice
        $data[3] = RawCodeCoverageData::fromLineCoverage([
            TEST_FILES_PATH . 'BankAccount.php' => [
                8  => 3,
                9  => -2,
                13 => 1,
                14 => 1,
                15 => 1,
                16 => -1,
                18 => 1,
                22 => 2,
                24 => 2,
                25 => -2,
                29 => 1,
                31 => 1,
                32 => -2,
                33 => -2,
            ],
        ]);

        $driver = new FakeDriver(...$data);
        $driver->markAsCollectingHitCounts();

        $filter = new Filter;
        $filter->includeFile(TEST_FILES_PATH . 'BankAccount.php');

        $coverage = new CodeCoverage($driver, $filter);

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
            time: 0.1,
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
            time: 0.2,
        );

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
            time: 0.3,
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
            time: 0.4,
        );

        return $coverage;
    }

    public function pathCoverageForBankAccount(): CodeCoverage
    {
        $data = $this->pathCoverageXdebugDataForBankAccount();

        $driver = new FakeDriver(...$data);

        $filter = new Filter;
        $filter->includeFile(TEST_FILES_PATH . 'BankAccount.php');

        $coverage = new CodeCoverage($driver, $filter);

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
            time: 0.5,
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
            time: 0.6,
        );

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
            time: 0.7,
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
            time: 0.8,
        );

        return $coverage;
    }

    public function pathCoverageForSourceWithoutNamespace(): CodeCoverage
    {
        $data = $this->pathCoverageXdebugDataForSourceWithoutNamespace();

        $driver = new FakeDriver(...$data);

        $filter = new Filter;
        $filter->includeFile(TEST_FILES_PATH . 'source_without_namespace.php');

        $coverage = new CodeCoverage($driver, $filter);

        $coverage->enableBranchAndPathCoverage();

        $coverage->start(
            'faketest',
            null,
            true,
        );

        $coverage->stop();

        return $coverage;
    }

    public function coverageForFileWithIgnoredLines(): CodeCoverage
    {
        $filter = new Filter;
        $filter->includeFile(TEST_FILES_PATH . 'source_with_ignore.php');

        $coverage = new CodeCoverage(
            $this->xdebugFakeForFileWithIgnoredLines(),
            $filter,
        );

        $coverage->start('FileWithIgnoredLines', null, true);
        $coverage->stop();

        return $coverage;
    }

    public function coverageForClassWithAnonymousFunction(): CodeCoverage
    {
        $filter = new Filter;
        $filter->includeFile(TEST_FILES_PATH . 'source_with_class_and_anonymous_function.php');

        $coverage = new CodeCoverage(
            $this->xdebugFakeForClassWithAnonymousFunction(),
            $filter,
        );

        $coverage->start('ClassWithAnonymousFunction', null, true);
        $coverage->stop();

        return $coverage;
    }

    public function coverageForClassesWithTraitsAndInheritance(): CodeCoverage
    {
        $filter = new Filter;
        $filter->includeFile(TEST_FILES_PATH . 'ClassView' . DIRECTORY_SEPARATOR . 'ChildClass.php');
        $filter->includeFile(TEST_FILES_PATH . 'ClassView' . DIRECTORY_SEPARATOR . 'ExampleClass.php');
        $filter->includeFile(TEST_FILES_PATH . 'ClassView' . DIRECTORY_SEPARATOR . 'ExampleTrait.php');
        $filter->includeFile(TEST_FILES_PATH . 'ClassView' . DIRECTORY_SEPARATOR . 'ParentClass.php');

        $coverage = new CodeCoverage(
            $this->xdebugFakeForClassesWithTraitsAndInheritance(),
            $filter,
        );

        $coverage->start('ClassesWithTraitsAndInheritance', null, true);
        $coverage->stop();

        return $coverage;
    }

    /**
     * Records line coverage for BankAccount.php where the lines are covered by
     * tests of different sizes (large, medium, small) and statuses (success and
     * failure) so that the corresponding rendering branches are exercised.
     */
    public function coverageForBankAccountWithVariousTestSizesAndStatuses(): CodeCoverage
    {
        $data = $this->lineCoverageXdebugDataForBankAccount();

        $data[] = RawCodeCoverageData::fromLineCoverage([
            TEST_FILES_PATH . 'BankAccount.php' => [
                8  => -1,
                9  => -2,
                13 => 1,
                14 => 1,
                15 => 1,
                16 => 1,
                18 => 1,
                22 => -1,
                24 => -1,
                25 => -2,
                29 => -1,
                31 => -1,
                32 => -2,
                33 => -2,
            ],
        ]);

        $driver = new FakeDriver(...$data);

        $filter = new Filter;
        $filter->includeFile(TEST_FILES_PATH . 'BankAccount.php');

        $coverage = new CodeCoverage($driver, $filter);

        $coverage->start('BankAccountTest::testBalanceIsInitiallyZero', TestSize::Large, true);
        $coverage->stop(
            true,
            TestStatus::Success,
            TargetCollection::fromArray([Target::forMethod(BankAccount::class, 'getBalance')]),
            time: 0.1,
        );

        $coverage->start('BankAccountTest::testBalanceCannotBecomeNegative', TestSize::Medium);
        $coverage->stop(
            true,
            TestStatus::Success,
            TargetCollection::fromArray([Target::forMethod(BankAccount::class, 'withdrawMoney')]),
            time: 0.2,
        );

        $coverage->start('BankAccountTest::testBalanceCannotBecomeNegative2', TestSize::Small);
        $coverage->stop(
            true,
            TestStatus::Success,
            TargetCollection::fromArray([Target::forMethod(BankAccount::class, 'depositMoney')]),
            time: 0.3,
        );

        $coverage->start('BankAccountTest::testDepositWithdrawMoney', TestSize::Large);
        $coverage->stop(
            true,
            TestStatus::Failure,
            TargetCollection::fromArray([
                Target::forMethod(BankAccount::class, 'getBalance'),
                Target::forMethod(BankAccount::class, 'depositMoney'),
                Target::forMethod(BankAccount::class, 'withdrawMoney'),
            ]),
            time: 0.4,
        );

        $coverage->start('BankAccountTest::testSetBalance', TestSize::Small);
        $coverage->stop(
            true,
            TestStatus::Success,
            TargetCollection::fromArray([Target::forMethod(BankAccount::class, 'setBalance')]),
            time: 0.5,
        );

        return $coverage;
    }

    /**
     * Records branch and path coverage for BankAccount.php where the covering
     * tests have different sizes so that the size-dependent rendering branches
     * in the branch and path source views are exercised.
     */
    public function pathCoverageForBankAccountWithVariousTestSizes(): CodeCoverage
    {
        $data = $this->pathCoverageXdebugDataForBankAccount();

        $driver = new FakeDriver(...$data);

        $filter = new Filter;
        $filter->includeFile(TEST_FILES_PATH . 'BankAccount.php');

        $coverage = new CodeCoverage($driver, $filter);

        $coverage->enableBranchAndPathCoverage();

        $coverage->start('BankAccountTest::testBalanceIsInitiallyZero', TestSize::Large, true);
        $coverage->stop(
            true,
            TestStatus::Success,
            TargetCollection::fromArray([Target::forMethod(BankAccount::class, 'getBalance')]),
            time: 0.5,
        );

        $coverage->start('BankAccountTest::testBalanceCannotBecomeNegative', TestSize::Medium);
        $coverage->stop(
            true,
            TestStatus::Success,
            TargetCollection::fromArray([Target::forMethod(BankAccount::class, 'withdrawMoney')]),
            time: 0.6,
        );

        $coverage->start('BankAccountTest::testBalanceCannotBecomeNegative2', TestSize::Small);
        $coverage->stop(
            true,
            TestStatus::Success,
            TargetCollection::fromArray([Target::forMethod(BankAccount::class, 'depositMoney')]),
            time: 0.7,
        );

        $coverage->start('BankAccountTest::testDepositWithdrawMoney', TestSize::Small);
        $coverage->stop(
            true,
            TestStatus::Success,
            TargetCollection::fromArray([
                Target::forMethod(BankAccount::class, 'getBalance'),
                Target::forMethod(BankAccount::class, 'depositMoney'),
                Target::forMethod(BankAccount::class, 'withdrawMoney'),
            ]),
            time: 0.8,
        );

        return $coverage;
    }

    /**
     * Records branch and path coverage for BankAccount.php where one test
     * covers some but not all branches and paths of a method, so that the
     * "partially covered" rendering states of the branch and path views are
     * exercised: mixed decision point markers, warning-classified source
     * lines, single-test popovers, and partial summary badges.
     *
     * The data also contains a function without branch and path data as well
     * as a function with more than 100 paths.
     */
    public function pathCoverageForBankAccountWithPartialBranchAndPathCoverage(): CodeCoverage
    {
        $manyPaths = [];

        for ($i = 0; $i < 101; $i++) {
            $manyPaths[$i] = [
                'path' => [0 => 0],
                'hit'  => 0,
            ];
        }

        $data = RawCodeCoverageData::fromXdebugWithPathCoverage([
            TEST_FILES_PATH . 'BankAccount.php' => [
                'lines' => [
                    13 => 1,
                    14 => 1,
                    16 => -1,
                    18 => 1,
                ],
                'functions' => [
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
                                    0 => 1,
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
                                    0 => 1,
                                ],
                            ],
                            9 => [
                                'op_start'   => 9,
                                'op_end'     => 12,
                                'line_start' => 14,
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
                                    0 => 1,
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
                    'BankAccount->declarationOnly' => [
                        'branches' => [
                        ],
                        'paths' => [
                        ],
                    ],
                    'BankAccount->complex' => [
                        'branches' => [
                            0 => [
                                'op_start'   => 0,
                                'op_end'     => 14,
                                'line_start' => 20,
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
                        'paths' => $manyPaths,
                    ],
                ],
            ],
        ]);

        $driver = new FakeDriver($data);

        $filter = new Filter;
        $filter->includeFile(TEST_FILES_PATH . 'BankAccount.php');

        $coverage = new CodeCoverage($driver, $filter);

        $coverage->enableBranchAndPathCoverage();

        $coverage->start('BankAccountTest::testSetBalance', TestSize::Small, true);
        $coverage->stop(
            true,
            TestStatus::Success,
            TargetCollection::fromArray([Target::forMethod(BankAccount::class, 'setBalance')]),
            time: 0.5,
        );

        return $coverage;
    }

    private function xdebugFakeForFileWithIgnoredLines(): Driver
    {
        return new FakeDriver(
            RawCodeCoverageData::fromLineCoverage(
                [
                    TEST_FILES_PATH . 'source_with_ignore.php' => [
                        2 => 1,
                        4 => -1,
                        6 => -1,
                    ],
                ],
            ),
        );
    }

    private function xdebugFakeForClassesWithTraitsAndInheritance(): Driver
    {
        return new FakeDriver(
            RawCodeCoverageData::fromLineCoverage(
                [
                    TEST_FILES_PATH . 'ClassView' . DIRECTORY_SEPARATOR . 'ChildClass.php' => [
                        11 => 1,
                    ],
                    TEST_FILES_PATH . 'ClassView' . DIRECTORY_SEPARATOR . 'ExampleClass.php' => [
                        9  => 1,
                        14 => -1,
                    ],
                    TEST_FILES_PATH . 'ClassView' . DIRECTORY_SEPARATOR . 'ExampleTrait.php' => [
                        9 => 1,
                    ],
                    TEST_FILES_PATH . 'ClassView' . DIRECTORY_SEPARATOR . 'ParentClass.php' => [
                        9 => -1,
                    ],
                ],
            ),
        );
    }

    private function xdebugFakeForClassWithAnonymousFunction(): Driver
    {
        return new FakeDriver(
            RawCodeCoverageData::fromLineCoverage(
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
                ],
            ),
        );
    }
}
