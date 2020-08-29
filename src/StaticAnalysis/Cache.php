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
use function file_exists;
use function file_get_contents;
use function file_put_contents;
use function filemtime;
use function hash;
use function serialize;
use function unserialize;
use SebastianBergmann\CodeCoverage\Directory;

abstract class Cache
{
    /**
     * @var string
     */
    private $directory;

    public function __construct(string $directory)
    {
        Directory::create($directory);

        $this->directory = $directory;
    }

    protected function cacheHas(string $filename, string $method): bool
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
     * @psalm-param list<class-string> $allowedClasses
     *
     * @return mixed
     */
    protected function cacheRead(string $filename, string $method, array $allowedClasses = [])
    {
        $options = ['allowed_classes' => false];

        if (!empty($allowedClasses)) {
            $options = ['allowed_classes' => $allowedClasses];
        }

        return unserialize(
            file_get_contents(
                $this->cacheFile($filename, $method)
            ),
            $options
        );
    }

    /**
     * @param mixed $data
     */
    protected function cacheWrite(string $filename, string $method, $data): void
    {
        file_put_contents(
            $this->cacheFile($filename, $method),
            serialize($data)
        );
    }

    protected function cacheFile(string $filename, string $method): string
    {
        return $this->directory . DIRECTORY_SEPARATOR . hash('sha256', $filename . $method);
    }
}
