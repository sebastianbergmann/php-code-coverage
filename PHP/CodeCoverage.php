<?php
/**
 * PHP_CodeCoverage
 *
 * Copyright (c) 2009-2012, Sebastian Bergmann <sb@sebastian-bergmann.de>.
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
 * @copyright  2009-2012 Sebastian Bergmann <sb@sebastian-bergmann.de>
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
 * @copyright  2009-2012 Sebastian Bergmann <sb@sebastian-bergmann.de>
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
    protected $cacheTokens = TRUE;

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
    protected $processUncoveredFilesFromWhitelist = FALSE;

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
     * Constructor.
     *
     * @param PHP_CodeCoverage_Driver $driver
     * @param PHP_CodeCoverage_Filter $filter
     */
    public function __construct(PHP_CodeCoverage_Driver $driver = NULL, PHP_CodeCoverage_Filter $filter = NULL)
    {
        if ($driver === NULL) {
            $driver = new PHP_CodeCoverage_Driver_Xdebug;
        }

        if ($filter === NULL) {
            $filter = new PHP_CodeCoverage_Filter;
        }

        $this->driver = $driver;
        $this->filter = $filter;
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
        $factory = new PHP_CodeCoverage_Report_Factory;

        return $factory->create($this);
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
        $this->processUncoveredFilesFromWhitelist();

        // We need to apply the blacklist filter a second time
        // when no whitelist is used.
        if (!$this->filter->hasWhitelist()) {
            $this->applyListsFilter($this->data);
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
     * @throws PHP_CodeCoverage_Exception
     */
    public function start($id, $clear = FALSE)
    {
        if (!is_bool($clear)) {
            throw PHP_CodeCoverage_Util_InvalidArgumentHelper::factory(
              1, 'boolean'
            );
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
     * @throws PHP_CodeCoverage_Exception
     */
    public function stop($append = TRUE)
    {
        if (!is_bool($append)) {
            throw PHP_CodeCoverage_Util_InvalidArgumentHelper::factory(
              1, 'boolean'
            );
        }

        $data = $this->driver->stop();
        $this->append($data, NULL, $append);

        $this->currentId = NULL;

        return $data;
    }

    /**
     * Appends code coverage data.
     *
     * @param array   $data
     * @param mixed   $id
     * @param boolean $append
     */
    public function append(array $data, $id = NULL, $append = TRUE)
    {
        if ($id === NULL) {
            $id = $this->currentId;
        }

        if ($id === NULL) {
            throw new PHP_CodeCoverage_Exception;
        }

        $this->applyListsFilter($data);
        $this->initializeFilesThatAreSeenTheFirstTime($data);

        if (!$append) {
            return;
        }

        if ($id != 'UNCOVERED_FILES_FROM_WHITELIST') {
            $this->applyCoversAnnotationFilter($data, $id);
        }

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
            if (!$this->filter->isFile($file)) {
                continue;
            }

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
                if (!$this->filter->isFiltered($file)) {
                    $this->data[$file] = $lines;
                }

                continue;
            }

            foreach ($lines as $line => $data) {
                if ($data !== NULL) {
                    if (!isset($this->data[$file][$line])) {
                        $this->data[$file][$line] = $data;
                    } else {
                        $this->data[$file][$line] = array_unique(
                          array_merge($this->data[$file][$line], $data)
                        );
                    }
                }
            }
        }

        $this->tests = array_merge($this->tests, $that->getTests());
    }

    /**
     * @param  boolean $flag
     * @throws PHP_CodeCoverage_Exception
     * @since  Method available since Release 1.1.0
     */
    public function setCacheTokens($flag)
    {
        if (!is_bool($flag)) {
            throw PHP_CodeCoverage_Util_InvalidArgumentHelper::factory(
              1, 'boolean'
            );
        }

        $this->cacheTokens = $flag;
    }

    /**
     * @param boolean $flag
     * @since Method available since Release 1.1.0
     */
    public function getCacheTokens()
    {
        return $this->cacheTokens;
    }

    /**
     * @param  boolean $flag
     * @throws PHP_CodeCoverage_Exception
     */
    public function setForceCoversAnnotation($flag)
    {
        if (!is_bool($flag)) {
            throw PHP_CodeCoverage_Util_InvalidArgumentHelper::factory(
              1, 'boolean'
            );
        }

        $this->forceCoversAnnotation = $flag;
    }

    /**
     * @param  boolean $flag
     * @throws PHP_CodeCoverage_Exception
     */
    public function setMapTestClassNameToCoveredClassName($flag)
    {
        if (!is_bool($flag)) {
            throw PHP_CodeCoverage_Util_InvalidArgumentHelper::factory(
              1, 'boolean'
            );
        }

        $this->mapTestClassNameToCoveredClassName = $flag;
    }

    /**
     * @param  boolean $flag
     * @throws PHP_CodeCoverage_Exception
     */
    public function setProcessUncoveredFilesFromWhitelist($flag)
    {
        if (!is_bool($flag)) {
            throw PHP_CodeCoverage_Util_InvalidArgumentHelper::factory(
              1, 'boolean'
            );
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
     */
    protected function applyListsFilter(&$data)
    {
        foreach (array_keys($data) as $filename) {
            if ($this->filter->isFiltered($filename)) {
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
            if ($this->filter->isFile($file) && !isset($this->data[$file])) {
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
        $uncoveredFiles = array_diff(
          $this->filter->getWhitelist(), array_keys($this->data)
        );

        foreach ($uncoveredFiles as $uncoveredFile) {
            if (!file_exists($uncoveredFile)) {
                continue;
            }

            if ($this->processUncoveredFilesFromWhitelist) {
                $this->processUncoveredFileFromWhitelist(
                  $uncoveredFile, $data, $uncoveredFiles
                );
            } else {
                $data[$uncoveredFile] = array();

                $lines = count(file($uncoveredFile));

                for ($i = 1; $i <= $lines; $i++) {
                    $data[$uncoveredFile][$i] = -1;
                }
            }
        }

        $this->append($data, 'UNCOVERED_FILES_FROM_WHITELIST');
    }

    /**
     * @param string $uncoveredFile
     * @param array  $data
     */
    protected function processUncoveredFileFromWhitelist($uncoveredFile, array &$data, array $uncoveredFiles)
    {
        if ($this->cacheTokens) {
            $tokens = PHP_Token_Stream_CachingFactory::get($uncoveredFile);
        } else {
            $tokens = new PHP_Token_Stream($uncoveredFile);
        }

        $classes    = $tokens->getClasses();
        $interfaces = $tokens->getInterfaces();
        $functions  = $tokens->getFunctions();
        unset($tokens);

        foreach (array_keys($classes) as $class) {
            if (class_exists($class, FALSE)) {
                continue;
            }
        }

        unset($classes);

        foreach (array_keys($interfaces) as $interface) {
            if (interface_exists($interface, FALSE)) {
                continue;
            }
        }

        unset($interfaces);

        foreach (array_keys($functions) as $function) {
            if (function_exists($function)) {
                continue;
            }
        }

        unset($functions);

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

                $data[$file] = $fileCoverage;
            }
        }
    }
}
