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
abstract class PHP_CodeCoverage_Report_Node implements Countable
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
     * @var array
     */
    protected $pathArray;

    /**
     * @var PHP_CodeCoverage_Report_Node
     */
    protected $parent;

    /**
     * @var string
     */
    protected $id;

    /**
     * Constructor.
     *
     * @param string                       $name
     * @param PHP_CodeCoverage_Report_Node $parent
     */
    public function __construct($name, PHP_CodeCoverage_Report_Node $parent = NULL)
    {
        if (substr($name, -1) == '/') {
            $name = substr($name, 0, -1);
        }

        $this->name   = $name;
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
    public function getId()
    {
        if ($this->id === NULL) {
            $parent = $this->getParent();

            if ($parent === NULL) {
                $this->id = 'index';
            } else {
                $parentId = $parent->getId();

                if ($parentId == 'index') {
                    $this->id = str_replace(':', '_', $this->name);
                } else {
                    $this->id = $parentId . '_' . $this->name;
                }
            }
        }

        return $this->id;
    }

    /**
     * @return string
     */
    public function getPath()
    {
        if ($this->path === NULL) {
            if ($this->parent === NULL) {
                $this->path = $this->name;
            } else {
                $this->path = $this->parent->getPath() . '/' . $this->name;
            }
        }

        return $this->path;
    }

    /**
     * @return array
     */
    public function getPathAsArray()
    {
        if ($this->pathArray === NULL) {
            if ($this->parent === NULL) {
                $this->pathArray = array();
            } else {
                $this->pathArray = $this->parent->getPathAsArray();
            }

            $this->pathArray[] = $this;
        }

        return $this->pathArray;
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
     * @param  boolean $asString
     * @return integer
     */
    public function getTestedClassesPercent($asString = TRUE)
    {
        return PHP_CodeCoverage_Util::percent(
          $this->getNumTestedClasses(),
          $this->getNumClasses(),
          $asString
        );
    }

    /**
     * Returns the percentage of traits that has been tested.
     *
     * @param  boolean $asString
     * @return integer
     */
    public function getTestedTraitsPercent($asString = TRUE)
    {
        return PHP_CodeCoverage_Util::percent(
          $this->getNumTestedTraits(),
          $this->getNumTraits(),
          $asString
        );
    }

    /**
     * Returns the percentage of methods that has been tested.
     *
     * @param  boolean $asString
     * @return integer
     */
    public function getTestedMethodsPercent($asString = TRUE)
    {
        return PHP_CodeCoverage_Util::percent(
          $this->getNumTestedMethods(),
          $this->getNumMethods(),
          $asString
        );
    }

    /**
     * Returns the percentage of executed lines.
     *
     * @param  boolean $asString
     * @return integer
     */
    public function getLineExecutedPercent($asString = TRUE)
    {
        return PHP_CodeCoverage_Util::percent(
          $this->getNumExecutedLines(),
          $this->getNumExecutableLines(),
          $asString
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
