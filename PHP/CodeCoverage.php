<?php
/**
 * PHPUnit
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
 * @category   Testing
 * @package    PHPUnit
 * @author     Sebastian Bergmann <sb@sebastian-bergmann.de>
 * @copyright  2009 Sebastian Bergmann <sb@sebastian-bergmann.de>
 * @license    http://www.opensource.org/licenses/bsd-license.php  BSD License
 * @link       http://github.com/sebastianbergmann/php-code-coverage
 * @since      File available since Release 1.0.0
 */

/**
 * Wrapper around Xdebug's code coverage functionality.
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
class PHP_CodeCoverage
{
    const STORAGE_ARRAY            = 0;
    const STORAGE_SPLOBJECTSTORAGE = 1;

    protected $storageType;

    protected $currentId;
    protected $data;

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
     * Constructor.
     *
     * @param integer $storageType
     */
    public function __construct($storageType = self::STORAGE_SPLOBJECTSTORAGE)
    {
        $this->storageType = $storageType;
        $this->clear();
    }

    /**
     * Wrapper for xdebug_start_code_coverage().
     *
     * @param mixed   $id
     * @param boolean $clear
     */
    public function start($id, $clear = FALSE)
    {
        if ($clear) {
            $this->clear();
        }

        $this->currentId = $id;

        xdebug_start_code_coverage(XDEBUG_CC_UNUSED | XDEBUG_CC_DEAD_CODE);
    }

    /**
     * Wrapper for xdebug_get_code_coverage() and xdebug_stop_code_coverage().
     */
    public function stop()
    {
        $codeCoverage = xdebug_get_code_coverage();
        xdebug_stop_code_coverage();
        $this->append($codeCoverage);
        $this->currentId = NULL;
    }

    /**
     * Appends code coverage data.
     *
     * @param array $data
     * @param mixed $id
     */
    public function append(array $data, $id = NULL)
    {
        if ($id === NULL) {
            $id = $this->currentId;
        }

        if ($id === NULL) {
            throw new InvalidArgumentException;
        }

        $dead       = array();
        $executable = array();

        foreach (array_keys($data) as $filename) {
            if ($this->isFiltered($filename)) {
                unset($data[$filename]);
            }
        }

        $newFilesToCollect = array_diff_key($data, $this->coveredFiles);

        if (count($newFilesToCollect) > 0) {
            $dead       = $this->getDeadLines($newFilesToCollect);
            $executable = $this->getExecutableLines($newFilesToCollect);

            foreach (array_keys($newFilesToCollect) as $filename) {
                $this->coveredFiles[$filename] = TRUE;
            }

            unset($newFilesToCollect);
        }

        if (is_object($id) && $id instanceof PHPUnit_Framework_TestCase) {
            require_once 'PHPUnit/Util/Test.php';

            $linesToBeCovered = PHPUnit_Util_Test::getLinesToBeCovered(
              get_class($id), $id->getName()
            );

            if (!empty($linesToBeCovered)) {
                $data = array_intersect_key($data, $linesToBeCovered);

                foreach (array_keys($data) as $filename) {
                    $data[$filename] = array_intersect_key(
                      $data[$filename], array_flip($linesToBeCovered[$filename])
                    );
                }
            }
        }

        $executed = $this->getExecutedLines($data);

        $this->data[$id] = array(
          'executed'   => $executed,
          'dead'       => $dead,
          'executable' => $executable,
        );
    }

    /**
     * Clears collected code coverage data.
     */
    public function clear()
    {
        switch ($this->storageType) {
            case STORAGE_ARRAY: {
                $this->data = array();
            }
            break;

            case STORAGE_SPLOBJECTSTORAGE:
            default: {
                $this->data = new SplObjectStorage;
            }
            break;
        }

        $this->coveredFiles = array();
        $this->currentId    = NULL;
    }

    /**
     * Returns only the executed lines.
     *
     * @param  array $data
     * @return array
     */
    public function getExecutedLines(array $data)
    {
        return $this->getLinesByStatus($data, 1);
    }

    /**
     * Returns only the executable lines.
     *
     * @param  array $data
     * @return array
     */
    public function getExecutableLines(array $data)
    {
        return $this->getLinesByStatus($data, array(-1, 1));
    }

    /**
     * Returns only the lines that were not executed.
     *
     * @param  array $data
     * @return array
     */
    public function getNotExecutedLines(array $data)
    {
        return $this->getLinesByStatus($data, -1);
    }

    /**
     * Returns only the dead code lines.
     *
     * @param  array $data
     * @return array
     */
    public function getDeadLines(array $data)
    {
        return $this->getLinesByStatus($data, -2);
    }

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
     * Returns a PHP_CodeCoverage_FilterIterator that iterates
     * over all files in the given directory that have the
     * given suffix and prefix.
     *
     * @param  string $directory
     * @param  string $suffix
     * @param  string $prefix
     * @return PHP_CodeCoverage_FilterIterator
     */
    protected function getIterator($directory, $suffix, $prefix)
    {
        return new PHP_CodeCoverage_FilterIterator(
          new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($directory)
          ),
          $suffix,
          $prefix
        );
    }

    /**
     * Filters lines by status.
     *
     * @param  array         $data
     * @param  array|integer $status
     * @return array
     */
    protected function getLinesByStatus(array $data, $status)
    {
        if (!is_array($status)) {
            $status = array($status);
        }

        $isFileCache = array();
        $result      = array();

        foreach ($data as $file => $coverage) {
            if (!isset($isFileCache[$file])) {
                $isFileCache[$file] = $this->isFile($file);
            }

            if (!$isFileCache[$file]) {
                continue;
            }

            $result[$file] = array();

            foreach ($coverage as $line => $_status) {
                if (in_array($_status, $status)) {
                    $result[$file][$line] = $_status;
                }
            }
        }

        return $result;
    }

    /**
     * @param string $filename
     */
    protected function isFile($filename)
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
    protected function isFiltered($filename)
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
}
?>
