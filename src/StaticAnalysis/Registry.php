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

use function implode;

/**
 * @internal This class is not covered by the backward compatibility promise for phpunit/php-code-coverage
 *
 * @no-named-arguments Parameter names are not covered by the backward compatibility promise for phpunit/php-code-coverage
 */
final class Registry
{
    /**
     * Analysers are shared per configuration, not globally: a CodeCoverage object that does not
     * cache static analysis results must not be served the analyser of a CodeCoverage object that
     * does, and vice versa. Sharing a single analyser regardless of the configuration means that
     * whichever object asks for an analyser first decides whether the static analysis cache is
     * used for the rest of the process.
     *
     * @var array<non-empty-string, FileAnalyser>
     */
    private static array $analysers = [];

    /**
     * @param ?non-empty-string $cacheDirectory
     */
    public static function analyser(?string $cacheDirectory, bool $useAnnotationsForIgnoringCode, bool $ignoreDeprecatedCode): FileAnalyser
    {
        $key = implode(
            "\0",
            [
                $cacheDirectory ?? '',
                $useAnnotationsForIgnoringCode ? '1' : '0',
                $ignoreDeprecatedCode ? '1' : '0',
            ],
        );

        if (isset(self::$analysers[$key])) {
            return self::$analysers[$key];
        }

        $sourceAnalyser = new ParsingSourceAnalyser;

        if ($cacheDirectory !== null) {
            $sourceAnalyser = new CachingSourceAnalyser(
                $cacheDirectory,
                $sourceAnalyser,
            );
        }

        self::$analysers[$key] = new FileAnalyser(
            $sourceAnalyser,
            $useAnnotationsForIgnoringCode,
            $ignoreDeprecatedCode,
        );

        return self::$analysers[$key];
    }
}
