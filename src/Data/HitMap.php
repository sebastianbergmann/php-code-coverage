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

use function max;

/**
 * Operations on maps of test index to hit count, the representation used for
 * line, branch, and path coverage.
 *
 * @internal This class is not covered by the backward compatibility promise for phpunit/php-code-coverage
 *
 * @no-named-arguments Parameter names are not covered by the backward compatibility promise for phpunit/php-code-coverage
 *
 * @phpstan-import-type TestIndexType from ProcessedCodeCoverageData
 */
final class HitMap
{
    /**
     * Hit counts for a test case that occurs in both operands are combined with max(),
     * not summed, because the same test case id on both sides means the same test
     * execution was observed twice.
     *
     * @see ProcessedCodeCoverageData::merge()
     *
     * @param array<TestIndexType, positive-int> $hit
     * @param array<TestIndexType, positive-int> $additionalHit
     *
     * @return array<TestIndexType, positive-int>
     */
    public static function merge(array $hit, array $additionalHit): array
    {
        self::mergeInto($hit, $additionalHit);

        return $hit;
    }

    /**
     * Merges the hit counts of the second operand into the first one in place,
     * combining them as merge() does. Merging the hit counts of many tests into
     * a growing map, one test after another, does not copy the map each time.
     *
     * @param array<TestIndexType, positive-int> $hit
     * @param array<TestIndexType, positive-int> $additionalHit
     *
     * @param-out array<TestIndexType, positive-int> $hit
     */
    public static function mergeInto(array &$hit, array $additionalHit): void
    {
        foreach ($additionalHit as $testIndex => $count) {
            $hit[$testIndex] = max($hit[$testIndex] ?? 0, $count);
        }
    }

    /**
     * @param array<TestIndexType, positive-int>  $hit
     * @param array<TestIndexType, TestIndexType> $remap
     *
     * @return array<TestIndexType, positive-int>
     */
    public static function withRemappedTestIndexes(array $hit, array $remap): array
    {
        $remapped = [];

        foreach ($hit as $testIndex => $count) {
            $remapped[$remap[$testIndex] ?? $testIndex] = $count;
        }

        return $remapped;
    }

    /**
     * @codeCoverageIgnore
     */
    private function __construct()
    {
    }
}
