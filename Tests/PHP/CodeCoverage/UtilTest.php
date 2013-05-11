<?php
/**
 * PHP_CodeCoverage
 *
 * Copyright (c) 2009-2013, Sebastian Bergmann <sebastian@phpunit.de>.
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
 * @copyright  2009-2013 Sebastian Bergmann <sebastian@phpunit.de>
 * @license    http://www.opensource.org/licenses/BSD-3-Clause  The BSD 3-Clause License
 * @link       http://github.com/sebastianbergmann/php-code-coverage
 * @since      File available since Release 1.0.0
 */

if (!defined('TEST_FILES_PATH')) {
    define(
      'TEST_FILES_PATH',
      dirname(dirname(dirname(__FILE__))) . DIRECTORY_SEPARATOR .
      '_files' . DIRECTORY_SEPARATOR
    );
}

require_once TEST_FILES_PATH . 'CoverageClassExtendedTest.php';
require_once TEST_FILES_PATH . 'CoverageClassTest.php';
require_once TEST_FILES_PATH . 'CoverageFunctionTest.php';
require_once TEST_FILES_PATH . 'CoverageFunctionParenthesesTest.php';
require_once TEST_FILES_PATH . 'CoverageFunctionParenthesesWhitespaceTest.php';
require_once TEST_FILES_PATH . 'CoverageMethodTest.php';
require_once TEST_FILES_PATH . 'CoverageMethodOneLineAnnotationTest.php';
require_once TEST_FILES_PATH . 'CoverageMethodParenthesesTest.php';
require_once TEST_FILES_PATH . 'CoverageMethodParenthesesWhitespaceTest.php';
require_once TEST_FILES_PATH . 'CoverageNoneTest.php';
require_once TEST_FILES_PATH . 'CoverageNotPrivateTest.php';
require_once TEST_FILES_PATH . 'CoverageNotProtectedTest.php';
require_once TEST_FILES_PATH . 'CoverageNotPublicTest.php';
require_once TEST_FILES_PATH . 'CoveragePrivateTest.php';
require_once TEST_FILES_PATH . 'CoverageProtectedTest.php';
require_once TEST_FILES_PATH . 'CoveragePublicTest.php';
require_once TEST_FILES_PATH . 'CoverageTwoDefaultClassAnnotations.php';
require_once TEST_FILES_PATH . 'CoveredClass.php';
require_once TEST_FILES_PATH . 'CoveredFunction.php';
require_once TEST_FILES_PATH . 'NamespaceCoverageClassExtendedTest.php';
require_once TEST_FILES_PATH . 'NamespaceCoverageClassTest.php';
require_once TEST_FILES_PATH . 'NamespaceCoverageCoversClassTest.php';
require_once TEST_FILES_PATH . 'NamespaceCoverageCoversClassPublicTest.php';
require_once TEST_FILES_PATH . 'NamespaceCoverageMethodTest.php';
require_once TEST_FILES_PATH . 'NamespaceCoverageNotPrivateTest.php';
require_once TEST_FILES_PATH . 'NamespaceCoverageNotProtectedTest.php';
require_once TEST_FILES_PATH . 'NamespaceCoverageNotPublicTest.php';
require_once TEST_FILES_PATH . 'NamespaceCoveragePrivateTest.php';
require_once TEST_FILES_PATH . 'NamespaceCoverageProtectedTest.php';
require_once TEST_FILES_PATH . 'NamespaceCoveragePublicTest.php';
require_once TEST_FILES_PATH . 'NamespaceCoveredClass.php';
require_once TEST_FILES_PATH . 'NotExistingCoveredElementTest.php';
require_once TEST_FILES_PATH . 'CoverageNothingTest.php';
/**
 * Tests for the PHP_CodeCoverage_Util class.
 *
 * @category   PHP
 * @package    CodeCoverage
 * @subpackage Tests
 * @author     Sebastian Bergmann <sebastian@phpunit.de>
 * @copyright  2009-2013 Sebastian Bergmann <sebastian@phpunit.de>
 * @license    http://www.opensource.org/licenses/BSD-3-Clause  The BSD 3-Clause License
 * @link       http://github.com/sebastianbergmann/php-code-coverage
 * @since      Class available since Release 1.0.0
 */
class PHP_CodeCoverage_UtilTest extends PHPUnit_Framework_TestCase
{
    /**
     * @covers PHP_CodeCoverage_Util::getLinesToBeIgnored
     */
    public function testGetLinesToBeIgnored()
    {
        $this->assertEquals(
          array(
             1 => TRUE,
             3 => TRUE,
             4 => TRUE,
             5 => TRUE,
             7 => TRUE,
             8 => TRUE,
             9 => TRUE,
            10 => TRUE,
            11 => TRUE,
            12 => TRUE,
            13 => TRUE,
            14 => TRUE,
            15 => TRUE,
            16 => TRUE,
            17 => TRUE,
            18 => TRUE,
            19 => TRUE,
            20 => TRUE,
            21 => TRUE,
            22 => TRUE,
            23 => TRUE,
            24 => TRUE,
            25 => TRUE,
            26 => TRUE,
            27 => TRUE,
            30 => TRUE,
            32 => TRUE,
            33 => TRUE,
            34 => TRUE,
            35 => TRUE,
            36 => TRUE,
            37 => TRUE,
            38 => TRUE,
          ),
          PHP_CodeCoverage_Util::getLinesToBeIgnored(
            TEST_FILES_PATH . 'source_with_ignore.php'
          )
        );
    }

    /**
     * @covers PHP_CodeCoverage_Util::getLinesToBeIgnored
     */
    public function testGetLinesToBeIgnored2()
    {
        $this->assertEquals(
          array(1 => TRUE),
          PHP_CodeCoverage_Util::getLinesToBeIgnored(
            TEST_FILES_PATH . 'source_without_ignore.php'
          )
        );
    }

    /**
     * @covers PHP_CodeCoverage_Util::getLinesToBeIgnored
     */
    public function testGetLinesToBeIgnoredOneLineAnnotations()
    {
        $this->assertEquals(
          array(
            1 => TRUE,
            2 => TRUE,
            3 => TRUE,
            4 => TRUE,
            5 => TRUE,
            6 => TRUE,
            7 => TRUE,
            8 => TRUE,
            9 => TRUE,
            10 => TRUE,
            11 => TRUE,
            12 => TRUE,
            13 => TRUE,
            14 => TRUE,
            17 => TRUE,
            19 => TRUE,
            22 => TRUE,
            23 => TRUE,
            27 => TRUE,
            28 => TRUE,
            29 => TRUE,
            30 => TRUE,
            31 => TRUE,
            32 => TRUE,
            33 => TRUE,
          ),
          PHP_CodeCoverage_Util::getLinesToBeIgnored(
            TEST_FILES_PATH . 'source_with_oneline_annotations.php'
          )
        );
    }


    /**
     * @covers PHP_CodeCoverage_Util::percent
     */
    public function testPercent()
    {
        $this->assertEquals(100, PHP_CodeCoverage_Util::percent(100, 0));
        $this->assertEquals(100, PHP_CodeCoverage_Util::percent(100, 100));
        $this->assertEquals(
          '100.00%', PHP_CodeCoverage_Util::percent(100, 100, TRUE)
        );
    }
}
