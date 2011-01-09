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
 * @since      File available since Release 1.1.0
 */

if (!defined('T_NAMESPACE')) {
    define('T_NAMESPACE', 377);
}

/**
 * Represents a file in the code coverage information tree.
 *
 * @category   PHP
 * @package    CodeCoverage
 * @author     Sebastian Bergmann <sb@sebastian-bergmann.de>
 * @copyright  2009-2011 Sebastian Bergmann <sb@sebastian-bergmann.de>
 * @license    http://www.opensource.org/licenses/bsd-license.php  BSD License
 * @version    Release: @package_version@
 * @link       http://github.com/sebastianbergmann/php-code-coverage
 * @since      Class available since Release 1.1.0
 */
class PHP_CodeCoverage_Report_Node_File extends PHP_CodeCoverage_Report_Node
{
    /**
     * @var string
     */
    protected $path;

    /**
     * @var array
     */
    protected $coverageData;

    /**
     * @var array
     */
    protected $testData;

    /**
     * @var integer
     */
    protected $numExecutableLines = 0;

    /**
     * @var integer
     */
    protected $numExecutedLines = 0;

    /**
     * @var array
     */
    protected $classes = array();

    /**
     * @var array
     */
    protected $functions = array();

    /**
     * @var integer
     */
    protected $numTestedClasses = 0;

    /**
     * @var integer
     */
    protected $numMethods = NULL;

    /**
     * @var integer
     */
    protected $numTestedMethods = NULL;

    /**
     * @var integer
     */
    protected $numTestedFunctions = NULL;

    /**
     * @var array
     */
    protected $startLines = array();

    /**
     * @var array
     */
    protected $endLines = array();

    /**
     * Constructor.
     *
     * @param  string                       $name
     * @param  string                       $path
     * @param  PHP_CodeCoverage_Report_Node $parent
     * @param  array                        $coverageData
     * @param  array                        $testData
     */
    public function __construct($name, $path, PHP_CodeCoverage_Report_Node $parent, array $coverageData, array $testData)
    {
        parent::__construct($name, $path, $parent);

        $this->coverageData = $coverageData;
        $this->testData     = $testData;
        $this->ignoredLines = PHP_CodeCoverage_Util::getLinesToBeIgnored(
                                $path
                              );

        $this->calculateStatistics();
    }

    /**
     * Returns the classes of this node.
     *
     * @return array
     */
    public function getClasses()
    {
        return $this->classes;
    }

    /**
     * Returns the functions of this node.
     *
     * @return array
     */
    public function getFunctions()
    {
        return $this->functions;
    }

    /**
     * Returns the number of executable lines.
     *
     * @return integer
     */
    public function getNumExecutableLines()
    {
        return $this->numExecutableLines;
    }

    /**
     * Returns the number of executed lines.
     *
     * @return integer
     */
    public function getNumExecutedLines()
    {
        return $this->numExecutedLines;
    }

    /**
     * Returns the number of classes.
     *
     * @return integer
     */
    public function getNumClasses()
    {
        return count($this->classes);
    }

    /**
     * Returns the number of tested classes.
     *
     * @return integer
     */
    public function getNumTestedClasses()
    {
        return $this->numTestedClasses;
    }

    /**
     * Returns the number of methods.
     *
     * @return integer
     */
    public function getNumMethods()
    {
        if ($this->numMethods === NULL) {
            $this->numMethods = 0;

            foreach ($this->classes as $class) {
                foreach ($class['methods'] as $method) {
                    if ($method['executableLines'] > 0) {
                        $this->numMethods++;
                    }
                }
            }
        }

        return $this->numMethods;
    }

    /**
     * Returns the number of tested methods.
     *
     * @return integer
     */
    public function getNumTestedMethods()
    {
        if ($this->numTestedMethods === NULL) {
            $this->numTestedMethods = 0;

            foreach ($this->classes as $class) {
                foreach ($class['methods'] as $method) {
                    if ($method['executableLines'] > 0 &&
                        $method['coverage'] == 100) {
                        $this->numTestedMethods++;
                    }
                }
            }
        }

        return $this->numTestedMethods;
    }

    /**
     * Returns the number of functions.
     *
     * @return integer
     */
    public function getNumFunctions()
    {
        return count($this->functions);
    }

    /**
     * Returns the number of tested functions.
     *
     * @return integer
     */
    public function getNumTestedFunctions()
    {
        if ($this->numTestedFunctions === NULL) {
            $this->numTestedFunctions = 0;

            foreach ($this->functions as $function) {
                if ($function['executableLines'] > 0 &&
                    $function['coverage'] == 100) {
                    $this->numTestedFunctions++;
                }
            }
        }

        return $this->numTestedFunctions;
    }

    /**
     * @param  integer $line
     * @return boolean
     */
    public function isLineIgnored($line)
    {
        return isset($this->ignoredLines[$line]);
    }

    /**
     * Calculates coverage statistics for the file.
     */
    protected function calculateStatistics()
    {
        $this->processClasses();
        $this->processFunctions();

        $max = count(file($this->getPath()));

        for ($lineNumber = 1; $lineNumber <= $max; $lineNumber++) {
            if (isset($this->startLines[$lineNumber])) {
                // Start line of a class.
                if (isset($this->startLines[$lineNumber]['methods'])) {
                    $currentClass = &$this->startLines[$lineNumber];
                }

                // Start line of a method.
                else {
                    $currentMethod = &$this->startLines[$lineNumber];
                }
            }

            if (isset($this->coverageData[$lineNumber]) &&
                $this->coverageData[$lineNumber] !== NULL) {
                if (isset($currentClass)) {
                    $currentClass['executableLines']++;
                }

                if (isset($currentMethod)) {
                    $currentMethod['executableLines']++;
                }

                $this->numExecutableLines++;

                if (count($this->coverageData[$lineNumber]) > 0 ||
                    isset($this->ignoredLines[$lineNumber])) {
                    if (isset($currentClass)) {
                        $currentClass['executedLines']++;
                    }

                    if (isset($currentMethod)) {
                        $currentMethod['executedLines']++;
                    }

                    $this->numExecutedLines++;
                }
            }

            if (isset($this->endLines[$lineNumber])) {
                // End line of a class.
                if (isset($this->endLines[$lineNumber]['methods'])) {
                    unset($currentClass);
                }

                // End line of a method.
                else {
                    unset($currentMethod);
                }
            }
        }

        foreach ($this->classes as $className => &$class) {
            foreach ($class['methods'] as &$method) {
                if ($method['executableLines'] > 0) {
                    $method['coverage'] = ($method['executedLines'] /
                                           $method['executableLines']) * 100;
                } else {
                    $method['coverage'] = 100;
                }

                $method['crap'] = PHP_CodeCoverage_Util::crap(
                  $method['ccn'], $method['coverage']
                );

                $class['ccn'] += $method['ccn'];
            }

            if ($class['executableLines'] > 0) {
                $class['coverage'] = ($class['executedLines'] /
                                      $class['executableLines']) * 100;
            } else {
                $class['coverage'] = 100;
            }

            if ($class['coverage'] == 100) {
                $this->numTestedClasses++;
            }

            $class['crap'] = PHP_CodeCoverage_Util::crap(
              $class['ccn'], $class['coverage']
            );
        }
    }

    /**
     *
     */
    protected function processClasses()
    {
        $tokens  = PHP_Token_Stream_CachingFactory::get($this->getPath());
        $classes = $tokens->getClasses();
        unset($tokens);

        foreach ($classes as $className => $class) {
            $this->classes[$className] = array(
              'methods'         => array(),
              'startLine'       => $class['startLine'],
              'executableLines' => 0,
              'executedLines'   => 0,
              'ccn'             => 0,
              'coverage'        => 0,
              'crap'            => 0,
              'package'         => $class['package']
            );

            $this->startLines[$class['startLine']] = &$this->classes[$className];
            $this->endLines[$class['endLine']]     = &$this->classes[$className];

            foreach ($class['methods'] as $methodName => $method) {
                $this->classes[$className]['methods'][$methodName] = array(
                  'signature'       => $method['signature'],
                  'startLine'       => $method['startLine'],
                  'executableLines' => 0,
                  'executedLines'   => 0,
                  'ccn'             => $method['ccn'],
                  'coverage'        => 0,
                  'crap'            => 0
                );

                $this->startLines[$method['startLine']] = &$this->classes[$className]['methods'][$methodName];
                $this->endLines[$method['endLine']]     = &$this->classes[$className]['methods'][$methodName];
            }
        }
    }

    /**
     *
     */
    protected function processFunctions()
    {
        $tokens    = PHP_Token_Stream_CachingFactory::get($this->getPath());
        $functions = $tokens->getFunctions();
        unset($tokens);

        foreach ($functions as $functionName => $function) {
            $this->functions[$functionName] = array(
              'signature'       => $function['signature'],
              'startLine'       => $function['startLine'],
              'executableLines' => 0,
              'executedLines'   => 0,
              'ccn'             => $function['ccn']
            );

            $this->startLines[$function['startLine']] = &$this->functions[$functionName];
            $this->endLines[$function['endLine']]     = &$this->functions[$functionName];
        }
    }
}
