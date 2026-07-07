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

/**
 * @internal This class is not covered by the backward compatibility promise for phpunit/php-code-coverage
 *
 * @no-named-arguments Parameter names are not covered by the backward compatibility promise for phpunit/php-code-coverage
 */
final class Registry
{
    private static ?FileAnalyser $analyser = null;

    /**
     * @param ?non-empty-string $cacheDirectory
     */
    public static function analyser(?string $cacheDirectory, bool $useAnnotationsForIgnoringCode, bool $ignoreDeprecatedCode): FileAnalyser
    {
        if (self::$analyser !== null) {
            return self::$analyser;
        }

        $sourceAnalyser = new ParsingSourceAnalyser;

        if ($cacheDirectory !== null) {
            $sourceAnalyser = new CachingSourceAnalyser(
                $cacheDirectory,
                $sourceAnalyser,
            );
        }

        self::$analyser = new FileAnalyser(
            $sourceAnalyser,
            $useAnnotationsForIgnoringCode,
            $ignoreDeprecatedCode,
        );

        return self::$analyser;
    }
}
