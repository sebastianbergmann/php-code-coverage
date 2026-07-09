<?php declare(strict_types=1);
/*
 * This file is part of phpunit/php-code-coverage.
 *
 * (c) Sebastian Bergmann <sebastian@phpunit.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace SebastianBergmann\CodeCoverage\Test;

/**
 * Bitmask representation of sets of test sizes.
 *
 * @internal This class is not covered by the backward compatibility promise for phpunit/php-code-coverage
 *
 * @no-named-arguments Parameter names are not covered by the backward compatibility promise for phpunit/php-code-coverage
 *
 * @phpstan-type TestSizeSet int<1, 7>
 * @phpstan-type TestSizeCounts array{1: int, 2: int, 3: int, 4: int, 5: int, 6: int, 7: int}
 */
final class TestSizes
{
    public const int SMALL  = 1;
    public const int MEDIUM = 2;
    public const int LARGE  = 4;
    public const int ALL    = self::SMALL | self::MEDIUM | self::LARGE;

    /**
     * All non-empty combinations of test sizes.
     *
     * @var list<TestSizeSet>
     */
    public const array COMBINATIONS = [
        self::SMALL,
        self::MEDIUM,
        self::LARGE,
        self::SMALL | self::MEDIUM,
        self::SMALL | self::LARGE,
        self::MEDIUM | self::LARGE,
        self::SMALL | self::MEDIUM | self::LARGE,
    ];

    /**
     * @var TestSizeCounts
     */
    public const array ZERO_COUNTS = [
        self::SMALL                              => 0,
        self::MEDIUM                             => 0,
        self::LARGE                              => 0,
        self::SMALL | self::MEDIUM               => 0,
        self::SMALL | self::LARGE                => 0,
        self::MEDIUM | self::LARGE               => 0,
        self::SMALL | self::MEDIUM | self::LARGE => 0,
    ];

    public static function bitFor(string $size): int
    {
        if ($size === 'small') {
            return self::SMALL;
        }

        if ($size === 'medium') {
            return self::MEDIUM;
        }

        if ($size === 'large') {
            return self::LARGE;
        }

        return 0;
    }

    private function __construct()
    {
    }
}
