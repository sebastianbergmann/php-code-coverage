<?php
/**
 * PHP_CodeCoverage
 *
 * Copyright (c) 2009-2014, Sebastian Bergmann <sebastian@phpunit.de>.
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
 * @subpackage Tests
 * @author     Sebastian Bergmann <sebastian@phpunit.de>
 * @copyright  2009-2014 Sebastian Bergmann <sebastian@phpunit.de>
 * @license    http://www.opensource.org/licenses/BSD-3-Clause  The BSD 3-Clause License
 * @link       http://github.com/sebastianbergmann/php-code-coverage
 */

/**
 * Tests for the PHP_CodeCoverage_Report_Node class.
 *
 * @category   PHP
 * @package    CodeCoverage
 * @subpackage Tests
 * @author     Sebastian Bergmann <sebastian@phpunit.de>
 * @copyright  2009-2014 Sebastian Bergmann <sebastian@phpunit.de>
 * @license    http://www.opensource.org/licenses/BSD-3-Clause  The BSD 3-Clause License
 * @link       http://github.com/sebastianbergmann/php-code-coverage
 */
class PHP_CodeCoverage_Report_NodeTest extends PHPUnit_Framework_TestCase
{
    public function testTrimsTailingSlashes()
    {
        $node = new DefaultNode('/SomeName.php/');
        $this->assertEquals('/SomeName.php', $node->getPath());
    }

    public function testNodeAcceptsRootScope()
    {
        $node = new DefaultNode('/');
        $this->assertEquals('/', $node->getPath());
    }
}

class DefaultNode extends PHP_CodeCoverage_Report_Node
{

    /**
     * Returns the classes of this node.
     *
     * @return array
     */
    public function getClasses()
    {
    }

    /**
     * Returns the traits of this node.
     *
     * @return array
     */
    public function getTraits()
    {
    }

    /**
     * Returns the functions of this node.
     *
     * @return array
     */
    public function getFunctions()
    {
    }

    /**
     * Returns the LOC/CLOC/NCLOC of this node.
     *
     * @return array
     */
    public function getLinesOfCode()
    {
    }

    /**
     * Returns the number of executable lines.
     *
     * @return integer
     */
    public function getNumExecutableLines()
    {
    }

    /**
     * Returns the number of executed lines.
     *
     * @return integer
     */
    public function getNumExecutedLines()
    {
    }

    /**
     * Returns the number of classes.
     *
     * @return integer
     */
    public function getNumClasses()
    {
    }

    /**
     * Returns the number of tested classes.
     *
     * @return integer
     */
    public function getNumTestedClasses()
    {
    }

    /**
     * Returns the number of traits.
     *
     * @return integer
     */
    public function getNumTraits()
    {
    }

    /**
     * Returns the number of tested traits.
     *
     * @return integer
     */
    public function getNumTestedTraits()
    {
    }

    /**
     * Returns the number of methods.
     *
     * @return integer
     */
    public function getNumMethods()
    {
    }

    /**
     * Returns the number of tested methods.
     *
     * @return integer
     */
    public function getNumTestedMethods()
    {
    }

    /**
     * Returns the number of functions.
     *
     * @return integer
     */
    public function getNumFunctions()
    {
    }

    /**
     * Returns the number of tested functions.
     *
     * @return integer
     */
    public function getNumTestedFunctions()
    {
    }

    /**
     * (PHP 5 &gt;= 5.1.0)<br/>
     * Count elements of an object
     * @link http://php.net/manual/en/countable.count.php
     * @return int The custom count as an integer.
     * </p>
     * <p>
     * The return value is cast to an integer.
     */
    public function count()
    {
    }
}