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
 * @subpackage Tests
 * @author     Sebastian Bergmann <sb@sebastian-bergmann.de>
 * @copyright  2009 Sebastian Bergmann <sb@sebastian-bergmann.de>
 * @license    http://www.opensource.org/licenses/bsd-license.php  BSD License
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

require_once TEST_FILES_PATH . '../TestCase.php';
require_once 'PHP/CodeCoverage/Report/Clover.php';

/**
 * Tests for the PHP_CodeCoverage_Report_Clover class.
 *
 * @category   PHP
 * @package    CodeCoverage
 * @subpackage Tests
 * @author     Sebastian Bergmann <sb@sebastian-bergmann.de>
 * @copyright  2009 Sebastian Bergmann <sb@sebastian-bergmann.de>
 * @license    http://www.opensource.org/licenses/bsd-license.php  BSD License
 * @version    Release: @package_version@
 * @link       http://github.com/sebastianbergmann/php-code-coverage
 * @since      Class available since Release 1.0.0
 */
class PHP_CodeCoverage_Report_CloverTest extends PHP_CodeCoverage_TestCase
{
    public function testCloverForBankAccountTest()
    {
        $clover = new PHP_CodeCoverage_Report_Clover;

        $this->assertStringMatchesFormat(
          '<?xml version="1.0" encoding="UTF-8"?>
<coverage generated="%i">
  <project timestamp="%i">
    <file name="%s/BankAccount.php">
      <class name="BankAccount" namespace="global">
        <metrics methods="4" coveredmethods="3" statements="10" coveredstatements="5" elements="14" coveredelements="8"/>
      </class>
      <line num="6" type="method" name="getBalance" count="2"/>
      <line num="8" type="stmt" count="2"/>
      <line num="11" type="method" name="setBalance" count="0"/>
      <line num="13" type="stmt" count="0"/>
      <line num="14" type="stmt" count="0"/>
      <line num="15" type="stmt" count="0"/>
      <line num="16" type="stmt" count="0"/>
      <line num="18" type="stmt" count="0"/>
      <line num="20" type="method" name="depositMoney" count="2"/>
      <line num="22" type="stmt" count="2"/>
      <line num="24" type="stmt" count="1"/>
      <line num="27" type="method" name="withdrawMoney" count="2"/>
      <line num="29" type="stmt" count="2"/>
      <line num="31" type="stmt" count="1"/>
      <metrics loc="34" ncloc="34" classes="1" methods="4" coveredmethods="3" statements="10" coveredstatements="5" elements="14" coveredelements="8"/>
    </file>
    <metrics files="1" loc="34" ncloc="34" classes="1" methods="4" coveredmethods="3" statements="10" coveredstatements="5" elements="14" coveredelements="8"/>
  </project>
</coverage>',
          $clover->process($this->getBankAccountCoverage())
        );
    }
}
?>
