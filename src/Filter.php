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

/**
 * Filter for whitelisting of code coverage information.
 */
final class Filter
{
    /**
     * Source files that are whitelisted.
     *
     * @var array
     */
    private $whitelistedFiles = [];

    /**
     * Adds a directory to the whitelist (recursively).
     */
    public function addDirectoryToWhitelist(string $directory, string $suffix = '.php', string $prefix = ''): void
    {
        $facade = new \File_Iterator_Facade;
        $files  = $facade->getFilesAsArray($directory, $suffix, $prefix);

        foreach ($files as $file) {
            $this->addFileToWhitelist($file);
        }
    }

    /**
     * Adds a file to the whitelist.
     */
    public function addFileToWhitelist(string $filename): void
    {
        $this->whitelistedFiles[\realpath($filename)] = true;
    }

    /**
     * Adds files to the whitelist.
     *
     * @param string[] $files
     */
    public function addFilesToWhitelist(array $files): void
    {
        foreach ($files as $file) {
            $this->addFileToWhitelist($file);
        }
    }

    /**
     * Removes a directory from the whitelist (recursively).
     */
    public function removeDirectoryFromWhitelist(string $directory, string $suffix = '.php', string $prefix = ''): void
    {
        $facade = new \File_Iterator_Facade;
        $files  = $facade->getFilesAsArray($directory, $suffix, $prefix);

        foreach ($files as $file) {
            $this->removeFileFromWhitelist($file);
        }
    }

    /**
     * Removes a file from the whitelist.
     */
    public function removeFileFromWhitelist(string $filename): void
    {
        $filename = \realpath($filename);

        unset($this->whitelistedFiles[$filename]);
    }

    /**
     * Checks whether a filename is a real filename.
     */
    public function isFile(string $filename): bool
    {
        if ($filename === '-' ||
            \strpos($filename, 'vfs://') === 0 ||
            \strpos($filename, 'xdebug://debug-eval') !== false ||
            \strpos($filename, 'eval()\'d code') !== false ||
            \strpos($filename, 'runtime-created function') !== false ||
            \strpos($filename, 'runkit created function') !== false ||
            \strpos($filename, 'assert code') !== false ||
            \strpos($filename, 'regexp code') !== false) {
            return false;
        }

        return \file_exists($filename);
    }

    /**
     * Checks whether or not a file is filtered.
     */
    public function isFiltered(string $filename): bool
    {
        if (!$this->isFile($filename)) {
            return true;
        }

        $filename = \realpath($filename);

        return !isset($this->whitelistedFiles[$filename]);
    }

    /**
     * Returns the list of whitelisted files.
     *
     * @return string[]
     */
    public function getWhitelist(): array
    {
        return \array_keys($this->whitelistedFiles);
    }

    /**
     * Returns whether this filter has a whitelist.
     */
    public function hasWhitelist(): bool
    {
        return !empty($this->whitelistedFiles);
    }

    /**
     * Returns the whitelisted files.
     *
     * @return string[]
     */
    public function getWhitelistedFiles(): array
    {
        return $this->whitelistedFiles;
    }

    /**
     * Sets the whitelisted files.
     */
    public function setWhitelistedFiles(array $whitelistedFiles): void
    {
        $this->whitelistedFiles = $whitelistedFiles;
    }
}
