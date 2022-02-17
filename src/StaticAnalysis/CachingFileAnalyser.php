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

use SebastianBergmann\CodeCoverage\Directory;

/**
 * @internal This class is not covered by the backward compatibility promise for phpunit/php-code-coverage
 */
final class CachingFileAnalyser implements FileAnalyser
{
    private const CACHE_FORMAT_VERSION = 2;

    /**
     * @var FileAnalyser
     */
    private $analyser;

    /**
     * @var array
     */
    private $cache = [];

    /**
     * @var string
     */
    private $directory;

    public function __construct(string $directory, FileAnalyser $analyser)
    {
        Directory::create($directory);

        $this->analyser  = $analyser;
        $this->directory = $directory;
    }

    public function classesIn(string $filename): array
    {
        if (!isset($this->cache[$filename])) {
            $this->process($filename);
        }

        return $this->cache[$filename]['classesIn'];
    }

    public function traitsIn(string $filename): array
    {
        if (!isset($this->cache[$filename])) {
            $this->process($filename);
        }

        return $this->cache[$filename]['traitsIn'];
    }

    public function functionsIn(string $filename): array
    {
        if (!isset($this->cache[$filename])) {
            $this->process($filename);
        }

        return $this->cache[$filename]['functionsIn'];
    }

    /**
     * @psalm-return array{linesOfCode: int, commentLinesOfCode: int, nonCommentLinesOfCode: int}
     */
    public function linesOfCodeFor(string $filename): array
    {
        if (!isset($this->cache[$filename])) {
            $this->process($filename);
        }

        return $this->cache[$filename]['linesOfCodeFor'];
    }

    public function executableLinesIn(string $filename): array
    {
        if (!isset($this->cache[$filename])) {
            $this->process($filename);
        }

        return $this->cache[$filename]['executableLinesIn'];
    }

    public function ignoredLinesFor(string $filename): array
    {
        if (!isset($this->cache[$filename])) {
            $this->process($filename);
        }

        return $this->cache[$filename]['ignoredLinesFor'];
    }

    public function process(string $filename): void
    {
        if ($this->has($filename)) {
            $this->cache[$filename] = $this->read($filename);

            return;
        }

        $this->cache[$filename] = [
            'classesIn'         => $this->analyser->classesIn($filename),
            'traitsIn'          => $this->analyser->traitsIn($filename),
            'functionsIn'       => $this->analyser->functionsIn($filename),
            'linesOfCodeFor'    => $this->analyser->linesOfCodeFor($filename),
            'ignoredLinesFor'   => $this->analyser->ignoredLinesFor($filename),
            'executableLinesIn' => $this->analyser->executableLinesIn($filename),
        ];

        $this->write($filename, $this->cache[$filename]);
    }

    private function has(string $filename): bool
    {
        $cacheFile = $this->cacheFile($filename);

        if (!is_file($cacheFile)) {
            return false;
        }

        if (filemtime($cacheFile) < filemtime($filename)) {
            return false;
        }

        return true;
    }

    /**
     * @return mixed
     */
    private function read(string $filename)
    {
        return unserialize(
            file_get_contents(
                $this->cacheFile($filename)
            ),
            ['allowed_classes' => false]
        );
    }

    /**
     * @param mixed $data
     */
    private function write(string $filename, $data): void
    {
        file_put_contents(
            $this->cacheFile($filename),
            serialize($data)
        );
    }

    private function cacheFile(string $filename): string
    {
        return $this->directory . DIRECTORY_SEPARATOR . hash('sha256', $filename . self::CACHE_FORMAT_VERSION);
    }
}
