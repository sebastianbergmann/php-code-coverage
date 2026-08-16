<?php declare(strict_types=1);
/*
 * This file is part of phpunit/php-code-coverage.
 *
 * (c) Sebastian Bergmann <sebastian@phpunit.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace SebastianBergmann\CodeCoverage\Report\Jsonl;

use function array_shift;
use function array_unique;
use function sort;

/**
 * @internal This class is not covered by the backward compatibility promise for phpunit/php-code-coverage
 *
 * @no-named-arguments Parameter names are not covered by the backward compatibility promise for phpunit/php-code-coverage
 */
final readonly class RangeEncoder
{
    /**
     * @param list<int> $lines
     *
     * @return list<non-empty-string>
     */
    public static function encode(array $lines): array
    {
        if ($lines === []) {
            return [];
        }

        $lines = array_unique($lines);

        sort($lines);

        $start = array_shift($lines);
        $end   = $start;

        $ranges = [];

        foreach ($lines as $line) {
            if ($line === $end + 1) {
                $end = $line;

                continue;
            }

            $ranges[] = self::encodeBounds($start, $end);
            $start    = $line;
            $end      = $line;
        }

        $ranges[] = self::encodeBounds($start, $end);

        return $ranges;
    }

    /**
     * @return non-empty-string
     */
    public static function encodeBounds(int $start, int $end): string
    {
        if ($start === $end) {
            return (string) $start;
        }

        return $start . '-' . $end;
    }
}
