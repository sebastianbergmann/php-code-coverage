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

final class CachingUncoveredFileAnalyser implements UncoveredFileAnalyser
{
    /**
     * @var string
     */
    private $directory;

    /**
     * @var UncoveredFileAnalyser
     */
    private $uncoveredFileAnalyser;

    public function __construct(string $directory, UncoveredFileAnalyser $uncoveredFileAnalyser)
    {
        Directory::create($directory);

        $this->directory             = $directory;
        $this->uncoveredFileAnalyser = $uncoveredFileAnalyser;
    }

    public function executableLinesIn(string $filename): array
    {
        if ($this->cacheHas($filename, __METHOD__)) {
            return $this->cacheRead($filename, __METHOD__);
        }

        $data = $this->uncoveredFileAnalyser->executableLinesIn($filename);

        $this->cacheWrite($filename, __METHOD__, $data);

        return $data;
    }

    private function cacheFile(string $filename, string $method): string
    {
        return $this->directory . DIRECTORY_SEPARATOR . hash('sha256', $filename . $method);
    }

    private function cacheHas(string $filename, string $method): bool
    {
        $cacheFile = $this->cacheFile($filename, $method);

        if (!file_exists($cacheFile)) {
            return false;
        }

        if (filemtime($cacheFile) < filemtime($filename)) {
            return false;
        }

        return true;
    }

    /**
     * @psalm-param list<string> $allowedClasses
     *
     * @return mixed
     */
    private function cacheRead(string $filename, string $method, array $allowedClasses = [])
    {
        $options = ['allowed_classes' => false];

        if (!empty($allowedClasses)) {
            $options = ['allowed_classes' => $allowedClasses];
        }

        return unserialize(
            file_get_contents(
                $this->cacheFile($filename, $method)
            ),
            [
                'allowed_classes' => $allowedClasses,
            ]
        );
    }

    /**
     * @param mixed $data
     */
    private function cacheWrite(string $filename, string $method, $data): void
    {
        file_put_contents(
            $this->cacheFile($filename, $method),
            serialize($data)
        );
    }
}
