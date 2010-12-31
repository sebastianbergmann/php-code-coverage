<?php
/**
 * PHP_CodeCoverage
 *
 * Copyright (c) 2009-2011, Sebastian Bergmann <sb@sebastian-bergmann.de>.
 * All rights reserved.
 *
 * Redistribution and use in source and binary forms, with or without
 * modification, are permitted provided that the following conditions
 * are met:
 *
 *   * Redistributions of source code must retain the above copyright
 *     notice, this list of conditions and the following disclaimer.
 *
 *   * Redistributions in binary form must reproduce the above copyright
 *     notice, this list of conditions and the following disclaimer in
 *     the documentation and/or other materials provided with the
 *     distribution.
 *
 *   * Neither the name of Sebastian Bergmann nor the names of his
 *     contributors may be used to endorse or promote products derived
 *     from this software without specific prior written permission.
 *
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS
 * "AS IS" AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT
 * LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS
 * FOR A PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE
 * COPYRIGHT OWNER OR CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT,
 * INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING,
 * BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES;
 * LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER
 * CAUSED AND ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT
 * LIABILITY, OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN
 * ANY WAY OUT OF THE USE OF THIS SOFTWARE, EVEN IF ADVISED OF THE
 * POSSIBILITY OF SUCH DAMAGE.
 *
 * @category   PHP
 * @package    CodeCoverage
 * @author     Sebastian Bergmann <sb@sebastian-bergmann.de>
 * @copyright  2009-2011 Sebastian Bergmann <sb@sebastian-bergmann.de>
 * @license    http://www.opensource.org/licenses/bsd-license.php  BSD License
 * @link       http://github.com/sebastianbergmann/php-code-coverage
 * @since      File available since Release 1.0.0
 */

require_once 'File/Iterator/Factory.php';

/**
 * Filter for blacklisting and whitelisting of code coverage information.
 *
 * @category   PHP
 * @package    CodeCoverage
 * @author     Sebastian Bergmann <sb@sebastian-bergmann.de>
 * @copyright  2009-2011 Sebastian Bergmann <sb@sebastian-bergmann.de>
 * @license    http://www.opensource.org/licenses/bsd-license.php  BSD License
 * @version    Release: @package_version@
 * @link       http://github.com/sebastianbergmann/php-code-coverage
 * @since      Class available since Release 1.0.0
 */
class PHP_CodeCoverage_Filter
{
    /**
     * Source files that are blacklisted.
     *
     * @var array
     */
    protected $blacklistedFiles = array(
      'DEFAULT' => array()
    );

    /**
     * Source files that are whitelisted.
     *
     * @var array
     */
    protected $whitelistedFiles = array();

    /**
     * Default PHP_CodeCoverage object.
     *
     * @var PHP_CodeCoverage
     */
    protected static $instance;

    /**
     * Returns the default instance.
     *
     * @return PHP_CodeCoverage_Filter
     */
    public static function getInstance()
    {
        if (self::$instance === NULL) {
            // @codeCoverageIgnoreStart
            self::$instance = new PHP_CodeCoverage_Filter;
        }
        // @codeCoverageIgnoreEnd

        return self::$instance;
    }

    /**
     * Adds a directory to the blacklist (recursively).
     *
     * @param string $directory
     * @param string $suffix
     * @param string $prefix
     * @param string $group
     */
    public function addDirectoryToBlacklist($directory, $suffix = '.php', $prefix = '', $group = 'DEFAULT')
    {
        $files = File_Iterator_Factory::getFileIterator(
          $directory, $suffix, $prefix
        );

        foreach ($files as $file) {
            $this->addFileToBlacklist($file->getPathName(), $group, FALSE);
        }
    }

    /**
     * Adds a file to the blacklist.
     *
     * @param string $filename
     * @param string $group
     */
    public function addFileToBlacklist($filename, $group = 'DEFAULT')
    {
        $this->blacklistedFiles[$group][realpath($filename)] = TRUE;
    }

    /**
     * Adds files to the blacklist.
     *
     * @param array  $files
     * @param string $group
     */
    public function addFilesToBlacklist(array $files, $group = 'DEFAULT')
    {
        foreach ($files as $file) {
            $this->addFileToBlacklist($file, $group);
        }
    }

    /**
     * Removes a directory from the blacklist (recursively).
     *
     * @param string $directory
     * @param string $suffix
     * @param string $prefix
     * @param string $group
     */
    public function removeDirectoryFromBlacklist($directory, $suffix = '.php', $prefix = '', $group = 'DEFAULT')
    {
        $files = File_Iterator_Factory::getFileIterator(
          $directory, $suffix, $prefix
        );

        foreach ($files as $file) {
            $this->removeFileFromBlacklist($file->getPathName(), $group);
        }
    }

    /**
     * Removes a file from the blacklist.
     *
     * @param string $filename
     * @param string $group
     */
    public function removeFileFromBlacklist($filename, $group = 'DEFAULT')
    {
        $filename = realpath($filename);

        if (isset($this->blacklistedFiles[$group][$filename])) {
            unset($this->blacklistedFiles[$group][$filename]);
        }
    }

    /**
     * Adds a directory to the whitelist (recursively).
     *
     * @param string $directory
     * @param string $suffix
     * @param string $prefix
     */
    public function addDirectoryToWhitelist($directory, $suffix = '.php', $prefix = '')
    {
        $files = File_Iterator_Factory::getFileIterator(
          $directory, $suffix, $prefix
        );

        foreach ($files as $file) {
            $this->addFileToWhitelist($file->getPathName(), FALSE);
        }
    }

    /**
     * Adds a file to the whitelist.
     *
     * When the whitelist is empty (default), blacklisting is used.
     * When the whitelist is not empty, whitelisting is used.
     *
     * @param string $filename
     */
    public function addFileToWhitelist($filename)
    {
        $this->whitelistedFiles[realpath($filename)] = TRUE;
    }

    /**
     * Adds files to the whitelist.
     *
     * @param array $files
     */
    public function addFilesToWhitelist(array $files)
    {
        foreach ($files as $file) {
            $this->addFileToWhitelist($file);
        }
    }

    /**
     * Removes a directory from the whitelist (recursively).
     *
     * @param string $directory
     * @param string $suffix
     * @param string $prefix
     */
    public function removeDirectoryFromWhitelist($directory, $suffix = '.php', $prefix = '')
    {
        $files = File_Iterator_Factory::getFileIterator(
          $directory, $suffix, $prefix
        );

        foreach ($files as $file) {
            $this->removeFileFromWhitelist($file->getPathName());
        }
    }

    /**
     * Removes a file from the whitelist.
     *
     * @param string $filename
     */
    public function removeFileFromWhitelist($filename)
    {
        $filename = realpath($filename);

        if (isset($this->whitelistedFiles[$filename])) {
            unset($this->whitelistedFiles[$filename]);
        }
    }

    /**
     * Checks whether a filename is a real filename.
     *
     * @param string $filename
     */
    public static function isFile($filename)
    {
        if ($filename == '-' ||
            strpos($filename, 'eval()\'d code') !== FALSE ||
            strpos($filename, 'runtime-created function') !== FALSE ||
            strpos($filename, 'assert code') !== FALSE ||
            strpos($filename, 'regexp code') !== FALSE) {
            return FALSE;
        }

        return TRUE;
    }

    /**
     * Checks whether or not a file is filtered.
     *
     * When the whitelist is empty (default), blacklisting is used.
     * When the whitelist is not empty, whitelisting is used.
     *
     * @param  string  $filename
     * @param  array   $groups
     * @param  boolean $ignoreWhitelist
     * @return boolean
     * @throws InvalidArgumentException
     */
    public function isFiltered($filename, array $groups = array('DEFAULT'), $ignoreWhitelist = FALSE)
    {
        if (!is_bool($ignoreWhitelist)) {
            throw new InvalidArgumentException;
        }

        $filename = realpath($filename);

        if (!$ignoreWhitelist && !empty($this->whitelistedFiles)) {
            return !isset($this->whitelistedFiles[$filename]);
        }

        $blacklistedFiles = array();

        foreach ($groups as $group) {
            if (isset($this->blacklistedFiles[$group])) {
                $blacklistedFiles = array_merge(
                  $blacklistedFiles,
                  $this->blacklistedFiles[$group]
                );
            }
        }

        return isset($blacklistedFiles[$filename]);
    }

    /**
     * Returns the list of blacklisted files.
     *
     * @return array
     */
    public function getBlacklist()
    {
        $blacklistedFiles = array();

        foreach ($this->blacklistedFiles as $group => $list) {
            $blacklistedFiles[$group] = array_keys($list);
        }

        return $blacklistedFiles;
    }

    /**
     * Returns the list of whitelisted files.
     *
     * @return array
     */
    public function getWhitelist()
    {
        return array_keys($this->whitelistedFiles);
    }
}
