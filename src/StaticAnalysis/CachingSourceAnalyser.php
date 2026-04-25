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

use const DIRECTORY_SEPARATOR;
use function file_get_contents;
use function file_put_contents;
use function hash;
use function hash_final;
use function hash_init;
use function hash_update;
use function hash_update_file;
use function implode;
use function is_file;
use function serialize;
use function sort;
use function strlen;
use function substr;
use function unserialize;
use FilesystemIterator;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use SebastianBergmann\CodeCoverage\Util\Filesystem;

/**
 * @internal This interface is not covered by the backward compatibility promise for phpunit/php-code-coverage
 *
 * @no-named-arguments Parameter names are not covered by the backward compatibility promise for phpunit/php-code-coverage
 */
final class CachingSourceAnalyser implements SourceAnalyser
{
    private static ?string $analysisFingerprint = null;

    /**
     * @var non-empty-string
     */
    private readonly string $directory;
    private readonly SourceAnalyser $sourceAnalyser;

    /**
     * @var non-negative-int
     */
    private int $cacheHits = 0;

    /**
     * @var non-negative-int
     */
    private int $cacheMisses = 0;

    public function __construct(string $directory, SourceAnalyser $sourceAnalyser)
    {
        Filesystem::createDirectory($directory);

        $this->directory      = $directory;
        $this->sourceAnalyser = $sourceAnalyser;
    }

    /**
     * @param non-empty-string $sourceCodeFile
     */
    public function analyse(string $sourceCodeFile, string $sourceCode, bool $useAnnotationsForIgnoringCode, bool $ignoreDeprecatedCode): AnalysisResult
    {
        $cacheFile = $this->cacheFile(
            $sourceCode,
            $useAnnotationsForIgnoringCode,
            $ignoreDeprecatedCode,
        );

        $cachedAnalysisResult = $this->read($cacheFile);

        if ($cachedAnalysisResult !== false) {
            $this->cacheHits++;

            return $cachedAnalysisResult;
        }

        $this->cacheMisses++;

        $analysisResult = $this->sourceAnalyser->analyse(
            $sourceCodeFile,
            $sourceCode,
            $useAnnotationsForIgnoringCode,
            $ignoreDeprecatedCode,
        );

        $this->write($cacheFile, $analysisResult);

        return $analysisResult;
    }

    /**
     * @return non-negative-int
     */
    public function cacheHits(): int
    {
        return $this->cacheHits;
    }

    /**
     * @return non-negative-int
     */
    public function cacheMisses(): int
    {
        return $this->cacheMisses;
    }

    /**
     * @param non-empty-string $cacheFile
     */
    private function read(string $cacheFile): AnalysisResult|false
    {
        if (!is_file($cacheFile)) {
            return false;
        }

        $data = file_get_contents($cacheFile);

        if ($data === false) {
            return false;
        }

        return unserialize(
            $data,
            [
                'allowed_classes' => [
                    AnalysisResult::class,
                    Class_::class,
                    Function_::class,
                    Interface_::class,
                    LinesOfCode::class,
                    Method::class,
                    Trait_::class,
                ],
            ],
        );
    }

    /**
     * @param non-empty-string $cacheFile
     */
    private function write(string $cacheFile, AnalysisResult $result): void
    {
        file_put_contents(
            $cacheFile,
            serialize($result),
        );
    }

    private function cacheFile(string $source, bool $useAnnotationsForIgnoringCode, bool $ignoreDeprecatedCode): string
    {
        $cacheKey = hash(
            'sha256',
            implode(
                "\0",
                [
                    $source,
                    self::fingerprintOfSourceCodeForStaticAnalysis(),
                    $useAnnotationsForIgnoringCode,
                    $ignoreDeprecatedCode,
                ],
            ),
        );

        return $this->directory . DIRECTORY_SEPARATOR . $cacheKey;
    }

    /**
     * Returns an SHA-256 fingerprint of every PHP file that contributes to the
     * shape and content of an {@see AnalysisResult} — i.e. everything under
     * `src/StaticAnalysis/`. The result is memoised for the lifetime of the
     * PHP process, so the directory walk happens at most once per run.
     *
     * Including this in the cache key means that any change to a visitor, an
     * analyser, or a value class automatically invalidates entries from the
     * previous code shape, without anyone having to remember to bump a
     * format-version constant.
     */
    private static function fingerprintOfSourceCodeForStaticAnalysis(): string
    {
        if (self::$analysisFingerprint !== null) {
            return self::$analysisFingerprint;
        }

        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator(__DIR__, FilesystemIterator::SKIP_DOTS),
        );

        $files = [];

        foreach ($iterator as $file) {
            if ($file->isFile() && $file->getExtension() === 'php') {
                $files[] = $file->getPathname();
            }
        }

        sort($files);

        $hash = hash_init('sha256');

        foreach ($files as $file) {
            // include the path relative to __DIR__ so file renames and moves
            // invalidate the cache, while the absolute install location does
            // not (which would otherwise differ per machine).
            hash_update($hash, substr($file, strlen(__DIR__)));
            hash_update_file($hash, $file);
        }

        return self::$analysisFingerprint = hash_final($hash);
    }
}
