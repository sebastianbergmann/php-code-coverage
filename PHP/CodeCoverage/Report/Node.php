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

/**
 * Base class for nodes in the code coverage information tree.
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
abstract class PHP_CodeCoverage_Report_Node
{
    /**
     * @var string
     */
    protected $name;

    /**
     * @var string
     */
    protected $path;

    /**
     * @var PHP_CodeCoverage_Report_Node
     */
    protected $parent;

    /**
     * Constructor.
     *
     * @param string                       $name
     * @param string                       $path
     * @param PHP_CodeCoverage_Report_Node $parent
     */
    public function __construct($name, $path, PHP_CodeCoverage_Report_Node $parent = NULL)
    {
        $this->name   = $name;
        $this->path   = $path;
        $this->parent = $parent;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @return string
     */
    public function getPath()
    {
        return $this->path;
    }

    /**
     * @return PHP_CodeCoverage_Report_Node
     */
    public function getParent()
    {
        return $this->parent;
    }

    /**
     * Returns the percentage of classes that has been tested.
     *
     * @return integer
     */
    public function getTestedClassesPercent()
    {
        return PHP_CodeCoverage_Util::percent(
          $this->getNumTestedClasses(),
          $this->getNumClasses(),
          TRUE
        );
    }

    /**
     * Returns the percentage of methods that has been tested.
     *
     * @return integer
     */
    public function getTestedMethodsPercent()
    {
        return PHP_CodeCoverage_Util::percent(
          $this->getNumTestedMethods(),
          $this->getNumMethods(),
          TRUE
        );
    }

    /**
     * Returns the percentage of executed lines.
     *
     * @return integer
     */
    public function getLineExecutedPercent()
    {
        return PHP_CodeCoverage_Util::percent(
          $this->getNumExecutedLines(),
          $this->getNumExecutableLines(),
          TRUE
        );
    }

    /**
     * Returns the classes of this node.
     *
     * @return array
     */
    abstract public function getClasses();

    /**
     * Returns the traits of this node.
     *
     * @return array
     */
    abstract public function getTraits();

    /**
     * Returns the functions of this node.
     *
     * @return array
     */
    abstract public function getFunctions();

    /**
     * Returns the LOC/CLOC/NCLOC of this node.
     *
     * @return array
     */
    abstract public function getLinesOfCode();

    /**
     * Returns the number of executable lines.
     *
     * @return integer
     */
    abstract public function getNumExecutableLines();

    /**
     * Returns the number of executed lines.
     *
     * @return integer
     */
    abstract public function getNumExecutedLines();

    /**
     * Returns the number of classes.
     *
     * @return integer
     */
    abstract public function getNumClasses();

    /**
     * Returns the number of tested classes.
     *
     * @return integer
     */
    abstract public function getNumTestedClasses();

    /**
     * Returns the number of traits.
     *
     * @return integer
     */
    abstract public function getNumTraits();

    /**
     * Returns the number of tested traits.
     *
     * @return integer
     */
    abstract public function getNumTestedTraits();

    /**
     * Returns the number of methods.
     *
     * @return integer
     */
    abstract public function getNumMethods();

    /**
     * Returns the number of tested methods.
     *
     * @return integer
     */
    abstract public function getNumTestedMethods();

    /**
     * Returns the number of functions.
     *
     * @return integer
     */
    abstract public function getNumFunctions();

    /**
     * Returns the number of tested functions.
     *
     * @return integer
     */
    abstract public function getNumTestedFunctions();
}
