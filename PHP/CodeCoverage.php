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

require_once 'PHP/CodeCoverage/Filter.php';
require_once 'PHP/CodeCoverage/Util.php';

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

    /**
     * @var PHP_CodeCoverage_Filter
     */
    protected $filter;

    /**
     * @var integer
     */
    protected $storageType;

    /**
     * @var mixed
     */
    protected $currentId;

    /**
     * List of covered files.
     *
     * @var array
     */
    protected $coveredFiles;

    /**
     * Raw code coverage data.
     *
     * @var array|SplObjectStorage
     */
    protected $data;

    /**
     * Summarized code coverage data.
     *
     * @var array
     */
    protected $summary;

    /**
     * Constructor.
     *
     * @param PHP_CodeCoverage_Filter $filter
     * @param integer                 $storageType
     */
    public function __construct(PHP_CodeCoverage_Filter $filter = NULL, $storageType = self::STORAGE_SPLOBJECTSTORAGE)
    {
        if ($filter === NULL) {
            $filter = new PHP_CodeCoverage_Filter;
        }

        $this->filter      = $filter;
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

        // Apply blacklist/whitelist filtering.
        foreach (array_keys($data) as $filename) {
            if ($this->filter->isFiltered($filename)) {
                unset($data[$filename]);
            }
        }

        // Process files that are covered for the first time.
        $newFilesToCollect = array_diff_key($data, $this->coveredFiles);

        if (count($newFilesToCollect) > 0) {
            $dead       = $this->getDeadLines($newFilesToCollect);
            $executable = $this->getExecutableLines($newFilesToCollect);

            foreach (array_keys($newFilesToCollect) as $filename) {
                $this->coveredFiles[$filename] = TRUE;
            }
        }

        unset($newFilesToCollect);

        // Apply @covers filtering.
        if (is_object($id) && $id instanceof PHPUnit_Framework_TestCase) {
            $linesToBeCovered = PHP_CodeCoverage_Util::getLinesToBeCovered(
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

        $this->data[$id] = array(
          'executed'   => $this->getExecutedLines($data),
          'dead'       => $dead,
          'executable' => $executable,
        );

        $this->summary = array();
    }

    /**
     * Returns summarized code coverage data.
     *
     * Format of the result array:
     *
     * <code>
     * array(
     *   "/tested/code.php" => array(
     *     linenumber => array(tests that executed the line)
     *   )
     * )
     * </code>
     *
     * @return array
     */
    public function getSummary()
    {
        if (empty($this->summary)) {
        }

        return $this->summary;
    }

    /**
     * Clears collected code coverage data.
     */
    public function clear()
    {
        switch ($this->storageType) {
            case self::STORAGE_ARRAY: {
                $this->data = array();
            }
            break;

            case self::STORAGE_SPLOBJECTSTORAGE:
            default: {
                $this->data = new SplObjectStorage;
            }
            break;
        }

        $this->coveredFiles = array();
        $this->summary      = array();
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
     * Returns the PHP_CodeCoverage_Filter used.
     *
     * @return PHP_CodeCoverage_Filter
     */
    public function filter()
    {
        return $this->filter;
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
                $isFileCache[$file] = $this->filter->isFile($file);
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
     * Processes whitelisted files that are not covered.
     */
    protected function processUncoveredFilesFromWhitelist()
    {
        $data = array();

        $uncoveredFiles = array_diff(
          $this->filter->getWhitelist(), $this->coveredFiles
        );

        foreach ($uncoveredFiles as $uncoveredFile) {
            xdebug_start_code_coverage(XDEBUG_CC_UNUSED | XDEBUG_CC_DEAD_CODE);
            include_once $uncoveredFile;
            $coverage = xdebug_get_code_coverage();
            xdebug_stop_code_coverage();

            if (isset($coverage[$uncoveredFile])) {
                foreach ($coverage[$uncoveredFile] as $line => $flag) {
                    if ($flag > 0) {
                        $coverage[$uncoveredFile][$line] = -1;
                    }
                }

                $data[$uncoveredFile]               = $coverage[$uncoveredFile];
                $this->coveredFiles[$uncoveredFile] = TRUE;
            }
        }

        switch ($this->storageType) {
            case self::STORAGE_ARRAY: {
                $id = 'UNCOVERED_FILES_FROM_WHITELIST';
            }
            break;

            case self::STORAGE_SPLOBJECTSTORAGE:
            default: {
                $id = new StdClass;
            }
            break;
        }

        $this->append($data, $id);
    }
}
?>
