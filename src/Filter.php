<?php
/*
 * This file is part of the php-code-coverage package.
 *
 * (c) Sebastian Bergmann <sebastian@phpunit.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace SebastianBergmann\CodeCoverage;

use SebastianBergmann\FileIterator\Facade as FileIteratorFacade;

/**
 * Filter for whitelisting of code coverage information.
 */
final class Filter implements Sieve
{
    /**
     * Source files that are whitelisted.
     *
     * @var array
     */
    private $whitelistedFiles = [];

    /**
     * Remembers the result of the `is_file()` calls.
     *
     * @var bool[]
     */
    private $isFileCallsCache = [];

    /** @inheritDoc */
    public function addDirectoryToWhitelist(string $directory, string $suffix = '.php', string $prefix = ''): void
    {
        $facade = new FileIteratorFacade;
        $files  = $facade->getFilesAsArray($directory, $suffix, $prefix);

        foreach ($files as $file) {
            $this->addFileToWhitelist($file);
        }
    }

    /** @inheritDoc */
    public function addFileToWhitelist(string $filename): void
    {
        $this->whitelistedFiles[\realpath($filename)] = true;
    }

    /** @inheritDoc */
    public function addFilesToWhitelist(array $files): void
    {
        foreach ($files as $file) {
            $this->addFileToWhitelist($file);
        }
    }

    /** @inheritDoc */
    public function removeDirectoryFromWhitelist(string $directory, string $suffix = '.php', string $prefix = ''): void
    {
        $facade = new FileIteratorFacade;
        $files  = $facade->getFilesAsArray($directory, $suffix, $prefix);

        foreach ($files as $file) {
            $this->removeFileFromWhitelist($file);
        }
    }

    /** @inheritDoc */
    public function removeFileFromWhitelist(string $filename): void
    {
        $filename = \realpath($filename);

        unset($this->whitelistedFiles[$filename]);
    }

    /** @inheritDoc */
    public function isFile(string $filename): bool
    {
        if (isset($this->isFileCallsCache[$filename])) {
            return $this->isFileCallsCache[$filename];
        }

        if ($filename === '-' ||
            \strpos($filename, 'vfs://') === 0 ||
            \strpos($filename, 'xdebug://debug-eval') !== false ||
            \strpos($filename, 'eval()\'d code') !== false ||
            \strpos($filename, 'runtime-created function') !== false ||
            \strpos($filename, 'runkit created function') !== false ||
            \strpos($filename, 'assert code') !== false ||
            \strpos($filename, 'regexp code') !== false ||
            \strpos($filename, 'Standard input code') !== false) {
            $isFile = false;
        } else {
            $isFile = \file_exists($filename);
        }

        $this->isFileCallsCache[$filename] = $isFile;

        return $isFile;
    }

    /** @inheritDoc */
    public function isFiltered(string $filename): bool
    {
        if (!$this->isFile($filename)) {
            return true;
        }

        return !isset($this->whitelistedFiles[$filename]);
    }

    /** @inheritDoc */
    public function getWhitelist(): array
    {
        return \array_keys($this->whitelistedFiles);
    }

    /** @inheritDoc */
    public function hasWhitelist(): bool
    {
        return !empty($this->whitelistedFiles);
    }

    /** @inheritDoc */
    public function getWhitelistedFiles(): array
    {
        return $this->whitelistedFiles;
    }

    /** @inheritDoc */
    public function setWhitelistedFiles(array $whitelistedFiles): void
    {
        $this->whitelistedFiles = $whitelistedFiles;
    }
}
