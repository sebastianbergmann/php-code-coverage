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

require_once 'PHP/CodeCoverage/Driver/Xdebug.php';
require_once 'PHP/CodeCoverage/Filter.php';
require_once 'PHP/CodeCoverage/Util.php';

/**
 * Provides collection functionality for PHP code coverage information.
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
    /**
     * @var PHP_CodeCoverage_Driver
     */
    protected $driver;

    /**
     * @var PHP_CodeCoverage_Filter
     */
    protected $filter;

    /**
     * @var boolean
     */
    protected $forceCoversAnnotation = FALSE;

    /**
     * @var mixed
     */
    protected $currentId;

    /**
     * List of covered files.
     *
     * @var array
     */
    protected $coveredFiles = array();

    /**
     * Raw code coverage data.
     *
     * @var array
     */
    protected $data = array();

    /**
     * Summarized code coverage data.
     *
     * @var array
     */
    protected $summary = array();

    /**
     * Test data.
     *
     * @var array
     */
    protected $tests = array();

    /**
     * Constructor.
     *
     * @param  PHP_CodeCoverage_Driver $driver
     * @param  PHP_CodeCoverage_Filter $filter
     * @param  boolean                 $forceCoversAnnotation
     * @throws InvalidArgumentException
     */
    public function __construct(PHP_CodeCoverage_Driver $driver = NULL, PHP_CodeCoverage_Filter $filter = NULL, $forceCoversAnnotation = FALSE)
    {
        if ($driver === NULL) {
            $driver = new PHP_CodeCoverage_Driver_Xdebug;
        }

        if ($filter === NULL) {
            $filter = new PHP_CodeCoverage_Filter;
        }

        if (!is_bool($forceCoversAnnotation)) {
            throw new InvalidArgumentException;
        }

        $this->driver                = $driver;
        $this->filter                = $filter;
        $this->forceCoversAnnotation = $forceCoversAnnotation;
    }

    /**
     * Start collection of code coverage information.
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

        $this->driver->start();
    }

    /**
     * Stop collection of code coverage information.
     */
    public function stop()
    {
        $this->append($this->driver->stop());
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

        $dir = dirname(__FILE__);

        foreach (array_keys($data) as $file) {
            if (strpos($file, $dir) === 0 ||
                substr($file, -17) == 'File/Iterator.php' ||
                substr($file, -25) == 'File/Iterator/Factory.php') {
                unset($data[$file]);
            }
        }

        unset($dir, $file);

        // Process files that are covered for the first time.
        $newFiles = array_diff_key($data, $this->coveredFiles);

        if (count($newFiles) > 0) {
            $dead       = $this->getLinesByStatus($newFiles, -2);
            $executable = $this->getLinesByStatus($newFiles, array(-1, 1));

            foreach (array_keys($newFiles) as $filename) {
                $this->coveredFiles[$filename] = TRUE;
            }
        } else {
            $dead       = array();
            $executable = array();
        }

        unset($newFiles);

        // Apply @covers filtering.
        if ($id instanceof PHPUnit_Framework_TestCase) {
            $linesToBeCovered = PHP_CodeCoverage_Util::getLinesToBeCovered(
              get_class($id), $id->getName()
            );
        } else {
            $linesToBeCovered = array();
        }

        if (!empty($linesToBeCovered)) {
            $data = array_intersect_key($data, $linesToBeCovered);

            foreach (array_keys($data) as $filename) {
                $data[$filename] = array_intersect_key(
                  $data[$filename], array_flip($linesToBeCovered[$filename])
                );
            }
        }

        else if ($this->forceCoversAnnotation) {
            $data = array();
        }

        // Apply blacklist/whitelist filtering.
        foreach (array_keys($data) as $filename) {
            if ($this->filter->isFiltered($filename)) {
                unset($data[$filename]);
            }
        }

        if (!empty($data)) {
            if ($id instanceof PHPUnit_Framework_TestCase) {
                $status           = $id->getStatus();
                $id               = get_class($id) . '::' . $id->getName();
                $this->tests[$id] = $status;
            }

            $this->data[$id] = array(
              'executed'   => $this->getLinesByStatus($data, 1),
              'dead'       => $dead,
              'executable' => $executable,
            );

            $this->summary = array();
        }
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
            foreach ($this->data as $test => $coverage) {
                foreach ($coverage['executed'] as $file => $lines) {
                    foreach ($lines as $line => $flag) {
                        if (!isset($this->summary[$file][$line][0])) {
                            $this->summary[$file][$line] = array();
                        }

                        if (isset($this->tests[$test])) {
                            $status = $this->tests[$test];
                        } else {
                            $status = NULL;
                        }

                        $this->summary[$file][$line][] = array(
                          'id' => $test, 'status' => $status
                        );
                    }
                }

                foreach ($coverage['executable'] as $file => $lines) {
                    foreach ($lines as $line => $flag) {
                        if ($flag == -1 &&
                            !isset($this->summary[$file][$line][0])) {
                            $this->summary[$file][$line] = -1;
                        }
                    }
                }

                foreach ($coverage['dead'] as $file => $lines) {
                    foreach ($lines as $line => $flag) {
                        if (!isset($this->summary[$file][$line][0])) {
                            $this->summary[$file][$line] = -2;
                        }
                    }
                }
            }

            foreach ($this->summary as &$file) {
                ksort($file);
            }

            ksort($this->summary);
        }

        return $this->summary;
    }

    /**
     * Clears collected code coverage data.
     */
    public function clear()
    {
        $this->data         = array();
        $this->coveredFiles = array();
        $this->summary      = array();
        $this->currentId    = NULL;
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
            $this->driver->start();
            include_once $uncoveredFile;
            $coverage = $this->driver->stop();

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

        $this->append($data, 'UNCOVERED_FILES_FROM_WHITELIST');
    }
}
?>
