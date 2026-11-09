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
     * @var array<non-empty-string, CachingSourceAnalyser>
     */
    private static array $cachingSourceAnalysers = [];

    /**
     * The executable lines of a file that were determined from its bytecode do
     * not depend on the configuration of the analyser, so they are shared.
     */
    private static ?BytecodeLines $bytecodeLines = null;

    /**
     * @param ?non-empty-string          $cacheDirectory
     * @param iterable<non-empty-string> $filesToAnalyse the files that are about to be analysed, so that the bytecode of those whose analysis is not cached yet is analysed together (see BytecodeLines::expect())
     */
    public static function analyser(?string $cacheDirectory, bool $useAnnotationsForIgnoringCode, bool $ignoreDeprecatedCode, ExecutableLinesClassifier $classifier = ExecutableLinesClassifier::Syntax, iterable $filesToAnalyse = []): FileAnalyser
    {
        $key = implode(
            "\0",
            [
                $cacheDirectory ?? '',
                $useAnnotationsForIgnoringCode ? '1' : '0',
                $ignoreDeprecatedCode ? '1' : '0',
                $classifier->value,
            ],
        );

        if (!isset(self::$analysers[$key])) {
            $sourceAnalyser = new ParsingSourceAnalyser;

            if ($classifier === ExecutableLinesClassifier::Bytecode) {
                $sourceAnalyser = new BytecodeSourceAnalyser(
                    $sourceAnalyser,
                    self::bytecodeLines(),
                );
            }

            if ($cacheDirectory !== null) {
                $sourceAnalyser = new CachingSourceAnalyser(
                    $cacheDirectory,
                    $sourceAnalyser,
                    $classifier->cacheKey(),
                );

                self::$cachingSourceAnalysers[$key] = $sourceAnalyser;
            }

            self::$analysers[$key] = new FileAnalyser(
                $sourceAnalyser,
                $useAnnotationsForIgnoringCode,
                $ignoreDeprecatedCode,
            );
        }

        if ($classifier === ExecutableLinesClassifier::Bytecode && $filesToAnalyse !== []) {
            if (isset(self::$cachingSourceAnalysers[$key])) {
                $filesToAnalyse = self::$cachingSourceAnalysers[$key]->uncachedFiles(
                    $filesToAnalyse,
                    $useAnnotationsForIgnoringCode,
                    $ignoreDeprecatedCode,
                );
            }

            self::bytecodeLines()->expect($filesToAnalyse);
        }

        return self::$analysers[$key];
    }

    private static function bytecodeLines(): BytecodeLines
    {
        if (self::$bytecodeLines === null) {
            self::$bytecodeLines = new BytecodeLines;
        }

        return self::$bytecodeLines;
    }
}
