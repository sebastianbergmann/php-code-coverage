<?php
/**
 * PHP_CodeCoverage
 *
 * Copyright (c) 2009, Sebastian Bergmann <sb@sebastian-bergmann.de>.
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
 * @copyright  2009 Sebastian Bergmann <sb@sebastian-bergmann.de>
 * @license    http://www.opensource.org/licenses/bsd-license.php  BSD License
 * @link       http://github.com/sebastianbergmann/php-code-coverage
 * @since      File available since Release 1.0.0
 */

require_once 'PHP/CodeCoverage/Filter/Iterator.php';

/**
 * 
 *
 * @category   PHP
 * @package    CodeCoverage
 * @author     Sebastian Bergmann <sb@sebastian-bergmann.de>
 * @copyright  2009 Sebastian Bergmann <sb@sebastian-bergmann.de>
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
    protected static $blacklistedFiles = array();

    /**
     * Source files that are whitelisted.
     *
     * @var array
     */
    protected static $whitelistedFiles = array();

    /**
     * List of covered files.
     *
     * @var array
     */
    protected $coveredFiles;

    /**
     * Adds a directory to the blacklist (recursively).
     *
     * @param  string $directory
     * @param  string $suffix
     * @param  string $prefix
     * @throws RuntimeException
     */
    public function addDirectoryToBlacklist($directory, $suffix = '.php', $prefix = '')
    {
        if (file_exists($directory)) {
            foreach ($this->getIterator($directory, $suffix, $prefix) as $file) {
                $this->addFileToFilter($file->getPathName());
            }
        } else {
            throw new RuntimeException($directory . ' does not exist');
        }
    }

    /**
     * Adds a new file to be filtered (blacklist).
     *
     * @param  string $filename
     * @throws RuntimeException
     */
    public function addFileToBlacklist($filename)
    {
        if (file_exists($filename)) {
            $filename = realpath($filename);

            if (!in_array($filename, $this->blacklistedFiles)) {
                $this->blacklistedFiles[] = $filename;
            }
        } else {
            throw new RuntimeException($filename . ' does not exist');
        }
    }

    /**
     * Removes a directory from the blacklist (recursively).
     *
     * @param  string $directory
     * @param  string $suffix
     * @param  string $prefix
     * @throws RuntimeException
     */
    public function removeDirectoryFromBlacklist($directory, $suffix = '.php', $prefix = '')
    {
        if (file_exists($directory)) {
            foreach ($this->getIterator($directory, $suffix, $prefix) as $file) {
                $this->removeFileFromFilter($file->getPathName());
            }
        } else {
            throw new RuntimeException($directory . ' does not exist');
        }
    }

    /**
     * Removes a file from the filter (blacklist).
     *
     * @param  string $filename
     * @throws RuntimeException
     */
    public function removeFileFromBlacklist($filename)
    {
        if (file_exists($filename)) {
            $filename = realpath($filename);

            foreach ($this->blacklistedFiles as $key => $_filename) {
                if ($filename == $_filename) {
                    unset($this->blacklistedFiles[$key]);
                }
            }
        } else {
            throw new RuntimeException($filename . ' does not exist');
        }
    }

    /**
     * Adds a directory to the whitelist (recursively).
     *
     * @param  string $directory
     * @param  string $suffix
     * @param  string $prefix
     * @throws RuntimeException
     */
    public function addDirectoryToWhitelist($directory, $suffix = '.php', $prefix = '')
    {
        if (file_exists($directory)) {
            foreach ($this->getIterator($directory, $suffix, $prefix) as $file) {
                $this->addFileToWhitelist($file->getPathName());
            }
        } else {
            throw new RuntimeException($directory . ' does not exist');
        }
    }

    /**
     * Adds a new file to the whitelist.
     *
     * When the whitelist is empty (default), blacklisting is used.
     * When the whitelist is not empty, whitelisting is used.
     *
     * @param  string $filename
     * @throws RuntimeException
     */
    public function addFileToWhitelist($filename)
    {
        if (file_exists($filename)) {
            $filename = realpath($filename);

            if (!in_array($filename, $this->whitelistedFiles)) {
                $this->whitelistedFiles[] = $filename;
            }
        } else {
            throw new RuntimeException($filename . ' does not exist');
        }
    }

    /**
     * Removes a directory from the whitelist (recursively).
     *
     * @param  string $directory
     * @param  string $suffix
     * @param  string $prefix
     * @throws RuntimeException
     */
    public function removeDirectoryFromWhitelist($directory, $suffix = '.php', $prefix = '')
    {
        if (file_exists($directory)) {
            foreach ($this->getIterator($directory, $suffix, $prefix) as $file) {
                $this->removeFileFromWhitelist($file->getPathName());
            }
        } else {
            throw new RuntimeException($directory . ' does not exist');
        }
    }

    /**
     * Removes a file from the whitelist.
     *
     * @param  string $filename
     * @throws RuntimeException
     */
    public function removeFileFromWhitelist($filename)
    {
        if (file_exists($filename)) {
            $filename = realpath($filename);

            foreach ($this->whitelistedFiles as $key => $_filename) {
                if ($filename == $_filename) {
                    unset($this->whitelistedFiles[$key]);
                }
            }
        } else {
            throw new RuntimeException($filename . ' does not exist');
        }
    }

    /**
     * @param string $filename
     */
    public function isFile($filename)
    {
        if (strpos($filename, 'eval()\'d code') ||
            strpos($filename, 'runtime-created function') ||
            strpos($filename, 'assert code') ||
            strpos($filename, 'regexp code')) {
            return FALSE;
        }

        return TRUE;
    }

    /**
     * @param  string $filename
     * @return boolean
     */
    public function isFiltered($filename)
    {
        $filename = realpath($filename);

        if (!empty($this->whitelistedFiles)) {
            return !in_array($filename, $this->whitelistedFiles);
        }

        if (in_array($filename, $this->blacklistedFiles)) {
            return TRUE;
        }

        return FALSE;
    }

    /**
     * Returns a PHP_CodeCoverage_Filter_Iterator that iterates
     * over all files in the given directory that have the
     * given suffix and prefix.
     *
     * @param  string $directory
     * @param  string $suffix
     * @param  string $prefix
     * @return PHP_CodeCoverage_Filter_Iterator
     */
    protected function getIterator($directory, $suffix, $prefix)
    {
        return new PHP_CodeCoverage_Filter_Iterator(
          new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($directory)
          ),
          $suffix,
          $prefix
        );
    }
}
?>
