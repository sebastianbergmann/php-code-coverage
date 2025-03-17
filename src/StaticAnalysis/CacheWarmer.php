<?php declare(strict_types=1);
/*
 * This file is part of phpunit/php-code-coverage.
 *
 * (c) Sebastian Bergmann <sebastian@phpunit.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace SebastianBergmann\CodeCoverage\StaticAnalysis;

use SebastianBergmann\CodeCoverage\Filter;

final class CacheWarmer
{
    /**
     * @return array{cacheHits: non-negative-int, cacheMisses: non-negative-int}
     */
    public function warmCache(string $cacheDirectory, bool $useAnnotationsForIgnoringCode, bool $ignoreDeprecatedCode, Filter $filter): array
    {
        $analyser = new CachingFileAnalyser(
            $cacheDirectory,
            new ParsingFileAnalyser(
                $useAnnotationsForIgnoringCode,
                $ignoreDeprecatedCode,
            ),
            $useAnnotationsForIgnoringCode,
            $ignoreDeprecatedCode,
        );

        $cacheHits   = 0;
        $cacheMisses = 0;

        foreach ($filter->files() as $file) {
            $statistics = $analyser->process($file);

            $cacheHits   += $statistics['cacheHits'];
            $cacheMisses += $statistics['cacheMisses'];
        }

        return [
            'cacheHits'   => $cacheHits,
            'cacheMisses' => $cacheMisses,
        ];
    }
}
