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
interface Sieve
{
    /**
     * Adds a directory to the whitelist (recursively).
     */
    public function addDirectoryToWhitelist(string $directory, string $suffix = '.php', string $prefix = ''): void;

    /**
     * Adds a file to the whitelist.
     */
    public function addFileToWhitelist(string $filename): void;

    /**
     * Adds files to the whitelist.
     *
     * @param string[] $files
     */
    public function addFilesToWhitelist(array $files): void;

    /**
     * Removes a directory from the whitelist (recursively).
     */
    public function removeDirectoryFromWhitelist(string $directory, string $suffix = '.php', string $prefix = ''): void;

    /**
     * Removes a file from the whitelist.
     */
    public function removeFileFromWhitelist(string $filename): void;

    /**
     * Checks whether a filename is a real filename.
     */
    public function isFile(string $filename): bool;

    /**
     * Checks whether or not a file is filtered.
     */
    public function isFiltered(string $filename): bool;

    /**
     * Returns the list of whitelisted files.
     *
     * @return string[]
     */
    public function getWhitelist(): array;

    /**
     * Returns whether this filter has a whitelist.
     */
    public function hasWhitelist(): bool;

    /**
     * Returns the whitelisted files.
     *
     * @return array<string, true>
     */
    public function getWhitelistedFiles(): array;

    /**
     * Sets the whitelisted files.
     *
     * @param array<string, true> $whitelistedFiles
     */
    public function setWhitelistedFiles(array $whitelistedFiles): void;
}
