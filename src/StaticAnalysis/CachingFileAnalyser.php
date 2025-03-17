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
use function implode;
use function is_file;
use function md5;
use function serialize;
use function unserialize;
use SebastianBergmann\CodeCoverage\Util\Filesystem;
use SebastianBergmann\CodeCoverage\Version;

/**
 * @internal This class is not covered by the backward compatibility promise for phpunit/php-code-coverage
 *
 * @phpstan-type CachedDataForFile array{
 *   interfacesIn: array<string, Interface_>,
 *   classesIn: array<string, Class_>,
 *   traitsIn: array<string, Trait_>,
 *   functionsIn: array<string, Function_>,
 *   linesOfCodeFor: LinesOfCode,
 *   ignoredLinesFor: LinesType,
 *   executableLinesIn: LinesType
 * }
 *
 * @phpstan-import-type LinesType from FileAnalyser
 */
final class CachingFileAnalyser implements FileAnalyser
{
    private readonly string $directory;
    private readonly FileAnalyser $analyser;
    private readonly bool $useAnnotationsForIgnoringCode;
    private readonly bool $ignoreDeprecatedCode;

    /**
     * @var array<non-empty-string, CachedDataForFile>
     */
    private array $cache = [];

    public function __construct(string $directory, FileAnalyser $analyser, bool $useAnnotationsForIgnoringCode, bool $ignoreDeprecatedCode)
    {
        Filesystem::createDirectory($directory);

        $this->analyser                      = $analyser;
        $this->directory                     = $directory;
        $this->useAnnotationsForIgnoringCode = $useAnnotationsForIgnoringCode;
        $this->ignoreDeprecatedCode          = $ignoreDeprecatedCode;
    }

    /**
     * @return array<string, Interface_>
     */
    public function interfacesIn(string $filename): array
    {
        if (!isset($this->cache[$filename])) {
            $this->process($filename);
        }

        return $this->cache[$filename]['interfacesIn'];
    }

    /**
     * @return array<string, Class_>
     */
    public function classesIn(string $filename): array
    {
        if (!isset($this->cache[$filename])) {
            $this->process($filename);
        }

        return $this->cache[$filename]['classesIn'];
    }

    /**
     * @return array<string, Trait_>
     */
    public function traitsIn(string $filename): array
    {
        if (!isset($this->cache[$filename])) {
            $this->process($filename);
        }

        return $this->cache[$filename]['traitsIn'];
    }

    /**
     * @return array<string, Function_>
     */
    public function functionsIn(string $filename): array
    {
        if (!isset($this->cache[$filename])) {
            $this->process($filename);
        }

        return $this->cache[$filename]['functionsIn'];
    }

    public function linesOfCodeFor(string $filename): LinesOfCode
    {
        if (!isset($this->cache[$filename])) {
            $this->process($filename);
        }

        return $this->cache[$filename]['linesOfCodeFor'];
    }

    /**
     * @return LinesType
     */
    public function executableLinesIn(string $filename): array
    {
        if (!isset($this->cache[$filename])) {
            $this->process($filename);
        }

        return $this->cache[$filename]['executableLinesIn'];
    }

    /**
     * @return LinesType
     */
    public function ignoredLinesFor(string $filename): array
    {
        if (!isset($this->cache[$filename])) {
            $this->process($filename);
        }

        return $this->cache[$filename]['ignoredLinesFor'];
    }

    /**
     * @return array{cacheHits: non-negative-int, cacheMisses: non-negative-int}
     */
    public function process(string $filename): array
    {
        $cache = $this->read($filename);

        if ($cache !== false) {
            $this->cache[$filename] = $cache;

            return [
                'cacheHits'   => 1,
                'cacheMisses' => 0,
            ];
        }

        $this->cache[$filename] = [
            'interfacesIn'      => $this->analyser->interfacesIn($filename),
            'classesIn'         => $this->analyser->classesIn($filename),
            'traitsIn'          => $this->analyser->traitsIn($filename),
            'functionsIn'       => $this->analyser->functionsIn($filename),
            'linesOfCodeFor'    => $this->analyser->linesOfCodeFor($filename),
            'ignoredLinesFor'   => $this->analyser->ignoredLinesFor($filename),
            'executableLinesIn' => $this->analyser->executableLinesIn($filename),
        ];

        $this->write($filename, $this->cache[$filename]);

        return [
            'cacheHits'   => 0,
            'cacheMisses' => 1,
        ];
    }

    /**
     * @return CachedDataForFile|false
     */
    private function read(string $filename): array|false
    {
        $cacheFile = $this->cacheFile($filename);

        if (!is_file($cacheFile)) {
            return false;
        }

        return unserialize(
            file_get_contents($cacheFile),
            [
                'allowed_classes' => [
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
     * @param CachedDataForFile $data
     */
    private function write(string $filename, array $data): void
    {
        file_put_contents(
            $this->cacheFile($filename),
            serialize($data),
        );
    }

    private function cacheFile(string $filename): string
    {
        $cacheKey = md5(
            implode(
                "\0",
                [
                    $filename,
                    file_get_contents($filename),
                    Version::id(),
                    $this->useAnnotationsForIgnoringCode,
                    $this->ignoreDeprecatedCode,
                ],
            ),
        );

        return $this->directory . DIRECTORY_SEPARATOR . $cacheKey;
    }
}
