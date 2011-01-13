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
 * @subpackage Tests
 * @author     Sebastian Bergmann <sb@sebastian-bergmann.de>
 * @copyright  2009-2011 Sebastian Bergmann <sb@sebastian-bergmann.de>
 * @license    http://www.opensource.org/licenses/bsd-license.php  BSD License
 * @link       http://github.com/sebastianbergmann/php-code-coverage
 * @since      File available since Release 1.1.0
 */

if (!defined('TEST_FILES_PATH')) {
    define(
      'TEST_FILES_PATH',
      dirname(dirname(dirname(dirname(__FILE__)))) . DIRECTORY_SEPARATOR .
      '_files' . DIRECTORY_SEPARATOR
    );
}

require_once TEST_FILES_PATH . '../TestCase.php';

/**
 * Tests for the PHP_CodeCoverage_Report_Factory class.
 *
 * @category   PHP
 * @package    CodeCoverage
 * @subpackage Tests
 * @author     Sebastian Bergmann <sb@sebastian-bergmann.de>
 * @copyright  2009-2011 Sebastian Bergmann <sb@sebastian-bergmann.de>
 * @license    http://www.opensource.org/licenses/bsd-license.php  BSD License
 * @version    Release: @package_version@
 * @link       http://github.com/sebastianbergmann/php-code-coverage
 * @since      Class available since Release 1.1.0
 */
class PHP_CodeCoverage_Report_FactoryTest extends PHP_CodeCoverage_TestCase
{
    public function testSomething()
    {
        $root = $this->getCoverageForBankAccount()->getReport();

        $this->assertEquals('/usr/local/src/code-coverage/Tests/_files/', $root->getName());
        $this->assertEquals('/usr/local/src/code-coverage/Tests/_files/', $root->getPath());
        $this->assertEquals(10, $root->getNumExecutableLines());
        $this->assertEquals(5, $root->getNumExecutedLines());
        $this->assertEquals(1, $root->getNumClasses());
        $this->assertEquals(0, $root->getNumTestedClasses());
        $this->assertEquals(4, $root->getNumMethods());
        $this->assertEquals(3, $root->getNumTestedMethods());
        $this->assertEquals(0, $root->getTestedClassesPercent());
        $this->assertEquals(75, $root->getTestedMethodsPercent());
        $this->assertEquals(50, $root->getLineExecutedPercent());
        $this->assertEquals(0, $root->getNumFunctions());
        $this->assertEquals(0, $root->getNumTestedFunctions());
        $this->assertNull($root->getParent());
        $this->assertEquals(array(), $root->getDirectories());
        #$this->assertEquals(array(), $root->getFiles());
        #$this->assertEquals(array(), $root->getChildNodes());

        $this->assertEquals(
          array(
            'BankAccount' => array(
              'methods' => array(
                'getBalance' => array(
                  'signature' => 'getBalance()',
                  'startLine' => 6,
                  'executableLines' => 1,
                  'executedLines' => 1,
                  'ccn' => 1,
                  'coverage' => 100,
                  'crap' => '1'
                ),
                'setBalance' => array(
                  'signature' => 'setBalance($balance)',
                  'startLine' => 11,
                  'executableLines' => 5,
                  'executedLines' => 0,
                  'ccn' => 2,
                  'coverage' => 0,
                  'crap' => 6
                ),
                'depositMoney' => array(
                  'signature' => 'depositMoney($balance)',
                  'startLine' => 20,
                  'executableLines' => 2,
                  'executedLines' => 2,
                  'ccn' => 1,
                  'coverage' => 100,
                  'crap' => '1'
                ),
                'withdrawMoney' => array(
                  'signature' => 'withdrawMoney($balance)',
                  'startLine' => 27,
                  'executableLines' => 2,
                  'executedLines' => 2,
                  'ccn' => 1,
                  'coverage' => 100,
                  'crap' => '1'
                ),
              ),
              'startLine' => 2,
              'executableLines' => 10,
              'executedLines' => 5,
              'ccn' => 5,
              'coverage' => 50,
              'crap' => '8.12',
              'package' => array(
                'namespace' => '',
                'fullPackage' => '',
                'category' => '',
                'package' => '',
                'subpackage' => ''
              )
            )
          ),
          $root->getClasses()
        );

        $this->assertEquals(array(), $root->getFunctions());
    }
}
