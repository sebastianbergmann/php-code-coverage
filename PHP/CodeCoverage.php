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

/**
 * Provides collection functionality for PHP code coverage information.
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
     * @var boolean
     */
    protected $mapTestClassNameToCoveredClassName = FALSE;

    /**
     * @var boolean
     */
    protected $processUncoveredFilesFromWhitelist = TRUE;

    /**
     * @var mixed
     */
    protected $currentId;

    /**
     * Code coverage data.
     *
     * @var array
     */
    protected $data = array();

    /**
     * Test data.
     *
     * @var array
     */
    protected $tests = array();

    /**
     * @var boolean
     */
    protected $isCodeCoverageTestSuite = FALSE;

    /**
     * @var boolean
     */
    protected $isFileIteratorTestSuite = FALSE;

    /**
     * @var boolean
     */
    protected $isTimerTestSuite = FALSE;

    /**
     * @var boolean
     */
    protected $isTokenStreamTestSuite = FALSE;

    /**
     * Default PHP_CodeCoverage object.
     *
     * @var PHP_CodeCoverage
     */
    protected static $instance;

    /**
     * Constructor.
     *
     * @param  PHP_CodeCoverage_Driver $driver
     * @param  PHP_CodeCoverage_Filter $filter
     * @throws InvalidArgumentException
     */
    public function __construct(PHP_CodeCoverage_Driver $driver = NULL, PHP_CodeCoverage_Filter $filter = NULL)
    {
        if ($driver === NULL) {
            $driver = new PHP_CodeCoverage_Driver_Xdebug;
        }

        if ($filter === NULL) {
            $filter = PHP_CodeCoverage_Filter::getInstance();
        }

        $this->driver = $driver;
        $this->filter = $filter;

        if (defined('PHP_CODECOVERAGE_TESTSUITE')) {
            $this->isCodeCoverageTestSuite = TRUE;
        }

        // @codeCoverageIgnoreStart
        if (defined('FILE_ITERATOR_TESTSUITE')) {
            $this->isFileIteratorTestSuite = TRUE;
        }

        if (defined('PHP_TIMER_TESTSUITE')) {
            $this->isTimerTestSuite = TRUE;
        }

        if (defined('PHP_TOKENSTREAM_TESTSUITE')) {
            $this->isTokenStreamTestSuite = TRUE;
        }
        // @codeCoverageIgnoreEnd
    }

    /**
     * Returns the default instance.
     *
     * @return PHP_CodeCoverage
     */
    public static function getInstance()
    {
        if (self::$instance === NULL) {
            // @codeCoverageIgnoreStart
            self::$instance = new PHP_CodeCoverage;
        }
        // @codeCoverageIgnoreEnd
        return self::$instance;
    }

    /**
     * Returns the PHP_CodeCoverage_Report_Node_* object graph
     * for this PHP_CodeCoverage object.
     *
     * @return PHP_CodeCoverage_Report_Node_Directory
     * @since  Method available since Release 1.1.0
     */
    public function getReport()
    {
        return PHP_CodeCoverage_Report_Factory::create($this);
    }

    /**
     * Clears collected code coverage data.
     */
    public function clear()
    {
        $this->currentId = NULL;
        $this->data      = array();
        $this->tests     = array();
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
     * Returns the collected code coverage data.
     *
     * @return array
     * @since  Method available since Release 1.1.0
     */
    public function getData()
    {
        if ($this->processUncoveredFilesFromWhitelist) {
            $this->processUncoveredFilesFromWhitelist();
        }

        return $this->data;
    }

    /**
     * Returns the test data.
     *
     * @return array
     * @since  Method available since Release 1.1.0
     */
    public function getTests()
    {
        return $this->tests;
    }

    /**
     * Start collection of code coverage information.
     *
     * @param  mixed   $id
     * @param  boolean $clear
     * @throws InvalidArgumentException
     */
    public function start($id, $clear = FALSE)
    {
        if (!is_bool($clear)) {
            throw new InvalidArgumentException;
        }

        if ($clear) {
            $this->clear();
        }

        $this->currentId = $id;

        $this->driver->start();
    }

    /**
     * Stop collection of code coverage information.
     *
     * @param  boolean $append
     * @return array
     * @throws InvalidArgumentException
     */
    public function stop($append = TRUE)
    {
        if (!is_bool($append)) {
            throw new InvalidArgumentException;
        }

        $data = $this->driver->stop();

        if ($append) {
            $this->append($data);
        }

        $this->currentId = NULL;

        return $data;
    }

    /**
     * Appends code coverage data.
     *
     * @param array $data
     * @param mixed $id
     * @param array $filterGroups
     */
    public function append(array $data, $id = NULL, array $filterGroups = array('DEFAULT'))
    {
        if ($id === NULL) {
            $id = $this->currentId;
        }

        if ($id === NULL) {
            throw new InvalidArgumentException;
        }

        $this->applySelfFilter($data);
        $this->applyListsFilter($data, $filterGroups);
        $this->initializeFilesThatAreSeenTheFirstTime($data);
        $this->applyCoversAnnotationFilter($data, $id);

        if (empty($data)) {
            return;
        }

        $status = NULL;

        if ($id instanceof PHPUnit_Framework_TestCase) {
            $status = $id->getStatus();
            $id     = get_class($id) . '::' . $id->getName();
        }

        else if ($id instanceof PHPUnit_Extensions_PhptTestCase) {
            $id = $id->getName();
        }

        $this->tests[$id] = $status;

        foreach ($data as $file => $lines) {
            foreach ($lines as $k => $v) {
                if ($v == 1) {
                    $this->data[$file][$k][] = $id;
                }
            }
        }
    }

    /**
     * Merges the data from another instance of PHP_CodeCoverage.
     *
     * @param PHP_CodeCoverage $that
     */
    public function merge(PHP_CodeCoverage $that)
    {
        foreach ($that->data as $file => $lines) {
            if (!isset($this->data[$file])) {
                $this->data[$file] = $lines;
                continue;
            }

            foreach ($lines as $line => $data) {
                if ($data !== NULL) {
                    $this->data[$file][$line] = array_unique(
                      array_merge($this->data[$file][$line], $data)
                    );
                }
            }
        }
    }

    /**
     * @param  boolean $flag
     * @throws InvalidArgumentException
     */
    public function setForceCoversAnnotation($flag)
    {
        if (!is_bool($flag)) {
            throw new InvalidArgumentException;
        }

        $this->forceCoversAnnotation = $flag;
    }

    /**
     * @param  boolean $flag
     * @throws InvalidArgumentException
     */
    public function setMapTestClassNameToCoveredClassName($flag)
    {
        if (!is_bool($flag)) {
            throw new InvalidArgumentException;
        }

        $this->mapTestClassNameToCoveredClassName = $flag;
    }

    /**
     * @param  boolean $flag
     * @throws InvalidArgumentException
     */
    public function setProcessUncoveredFilesFromWhitelist($flag)
    {
        if (!is_bool($flag)) {
            throw new InvalidArgumentException;
        }

        $this->processUncoveredFilesFromWhitelist = $flag;
    }

    /**
     * Applies the @covers annotation filtering.
     *
     * @param array $data
     * @param mixed $id
     */
    protected function applyCoversAnnotationFilter(&$data, $id)
    {
        if ($id instanceof PHPUnit_Framework_TestCase) {
            $testClassName    = get_class($id);
            $linesToBeCovered = PHP_CodeCoverage_Util::getLinesToBeCovered(
              $testClassName, $id->getName()
            );

            if ($this->mapTestClassNameToCoveredClassName &&
                empty($linesToBeCovered)) {
                $testedClass = substr($testClassName, 0, -4);

                if (class_exists($testedClass)) {
                    $class = new ReflectionClass($testedClass);

                    $linesToBeCovered = array(
                      $class->getFileName() => range(
                        $class->getStartLine(), $class->getEndLine()
                      )
                    );
                }
            }
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
    }

    /**
     * Applies the blacklist/whitelist filtering.
     *
     * @param array $data
     * @param array $filterGroups
     */
    protected function applyListsFilter(&$data, $filterGroups)
    {
        foreach (array_keys($data) as $filename) {
            if ($this->filter->isFiltered($filename, $filterGroups)) {
                unset($data[$filename]);
            }
        }
    }

    /**
     * Filters sourcecode files from PHP_CodeCoverage, PHP_TokenStream,
     * Text_Template, and File_Iterator.
     *
     * @param array $data
     * @codeCoverageIgnore
     */
    protected function applySelfFilter(&$data)
    {
        foreach (array_keys($data) as $filename) {
            if (!$this->filter->isFile($filename)) {
                unset($data[$filename]);
                continue;
            }

            if (!$this->isCodeCoverageTestSuite &&
                strpos($filename, dirname(__FILE__)) === 0) {
                unset($data[$filename]);
                continue;
            }

            if (!$this->isFileIteratorTestSuite &&
                (substr($filename, -17) == 'File/Iterator.php' ||
                 substr($filename, -25) == 'File/Iterator/Factory.php')) {
                unset($data[$filename]);
                continue;
            }

            if (!$this->isTimerTestSuite &&
                (substr($filename, -13) == 'PHP/Timer.php')) {
                unset($data[$filename]);
                continue;
            }

            if (!$this->isTokenStreamTestSuite &&
                (substr($filename, -13) == 'PHP/Token.php' ||
                 substr($filename, -20) == 'PHP/Token/Stream.php' ||
                 substr($filename, -35) == 'PHP/Token/Stream/CachingFactory.php')) {
                unset($data[$filename]);
                continue;
            }

            if (substr($filename, -17) == 'Text/Template.php') {
                unset($data[$filename]);
            }
        }
    }

    /**
     * @since Method available since Release 1.1.0
     */
    protected function initializeFilesThatAreSeenTheFirstTime($data)
    {
        foreach ($data as $file => $lines) {
            if (!isset($this->data[$file])) {
                $this->data[$file] = array();

                foreach ($lines as $k => $v) {
                    $this->data[$file][$k] = $v == -2 ? NULL : array();
                }
            }
        }
    }

    /**
     * Processes whitelisted files that are not covered.
     */
    protected function processUncoveredFilesFromWhitelist()
    {
        $data           = array();
        $includedFiles  = array_flip(get_included_files());
        $uncoveredFiles = array_diff(
          $this->filter->getWhitelist(), array_keys($this->data)
        );

        foreach ($uncoveredFiles as $uncoveredFile) {
            $this->driver->start();
            include_once $uncoveredFile;
            $coverage = $this->driver->stop();

            foreach ($coverage as $file => $fileCoverage) {
                if (!isset($data[$file]) &&
                    in_array($file, $uncoveredFiles)) {
                    foreach (array_keys($fileCoverage) as $key) {
                        if ($fileCoverage[$key] == 1) {
                            $fileCoverage[$key] = -1;
                        }
                    }

                    $data[$file]          = $fileCoverage;
                    $includedFiles[$file] = TRUE;
                }
            }
        }

        $this->append($data, 'UNCOVERED_FILES_FROM_WHITELIST');
    }
}
