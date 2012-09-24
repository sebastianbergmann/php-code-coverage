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
 * @subpackage Tests
 * @author     Sebastian Bergmann <sb@sebastian-bergmann.de>
 * @copyright  2009-2012 Sebastian Bergmann <sb@sebastian-bergmann.de>
 * @license    http://www.opensource.org/licenses/BSD-3-Clause  The BSD 3-Clause License
 * @link       http://github.com/sebastianbergmann/php-code-coverage
 * @since      File available since Release 1.0.0
 */

/**
 * Abstract base class for test case classes.
 *
 * @category   PHP
 * @package    CodeCoverage
 * @subpackage Tests
 * @author     Sebastian Bergmann <sb@sebastian-bergmann.de>
 * @copyright  2009-2012 Sebastian Bergmann <sb@sebastian-bergmann.de>
 * @license    http://www.opensource.org/licenses/BSD-3-Clause  The BSD 3-Clause License
 * @link       http://github.com/sebastianbergmann/php-code-coverage
 * @since      Class available since Release 1.0.0
 */
abstract class PHP_CodeCoverage_TestCase extends PHPUnit_Framework_TestCase
{
    protected function getXdebugDataForBankAccount()
    {
        return array(
          array(
            TEST_FILES_PATH . 'BankAccount.php' => array(
               8 =>  1,
               9 => -2,
              13 => -1,
              14 => -1,
              15 => -1,
              16 => -1,
              18 => -1,
              22 => -1,
              24 => -1,
              25 => -2,
              29 => -1,
              31 => -1,
              32 => -2
            )
          ),
          array(
            TEST_FILES_PATH . 'BankAccount.php' => array(
               8 => 1,
              13 => 1,
              16 => 1,
              29 => 1,
            )
          ),
          array(
            TEST_FILES_PATH . 'BankAccount.php' => array(
               8 => 1,
              13 => 1,
              16 => 1,
              22 => 1,
            )
          ),
          array(
            TEST_FILES_PATH . 'BankAccount.php' => array(
               8 => 1,
              13 => 1,
              14 => 1,
              15 => 1,
              18 => 1,
              22 => 1,
              24 => 1,
              29 => 1,
              31 => 1,
            )
          )
        );
    }

    protected function getCoverageForBankAccount()
    {
        $data = $this->getXdebugDataForBankAccount();

        $stub = $this->getMock('PHP_CodeCoverage_Driver_Xdebug');
        $stub->expects($this->any())
             ->method('stop')
             ->will($this->onConsecutiveCalls(
               $data[0], $data[1], $data[2], $data[3]
             ));

        $coverage = new PHP_CodeCoverage($stub, new PHP_CodeCoverage_Filter);

        $coverage->start(
          new BankAccountTest('testBalanceIsInitiallyZero'), TRUE
        );
        $coverage->stop();

        $coverage->start(
          new BankAccountTest('testBalanceCannotBecomeNegative')
        );
        $coverage->stop();

        $coverage->start(
          new BankAccountTest('testBalanceCannotBecomeNegative2')
        );
        $coverage->stop();

        $coverage->start(
          new BankAccountTest('testDepositWithdrawMoney')
        );
        $coverage->stop();

        return $coverage;
    }

    protected function getCoverageForBankAccountForFirstTwoTests()
    {
        $data = $this->getXdebugDataForBankAccount();

        $stub = $this->getMock('PHP_CodeCoverage_Driver_Xdebug');
        $stub->expects($this->any())
             ->method('stop')
             ->will($this->onConsecutiveCalls(
               $data[0], $data[1]
             ));

        $coverage = new PHP_CodeCoverage($stub, new PHP_CodeCoverage_Filter);

        $coverage->start(
          new BankAccountTest('testBalanceIsInitiallyZero'), TRUE
        );
        $coverage->stop();

        $coverage->start(
          new BankAccountTest('testBalanceCannotBecomeNegative')
        );
        $coverage->stop();

        return $coverage;
    }

    protected function getCoverageForBankAccountForLastTwoTests()
    {
        $data = $this->getXdebugDataForBankAccount();

        $stub = $this->getMock('PHP_CodeCoverage_Driver_Xdebug');
        $stub->expects($this->any())
             ->method('stop')
             ->will($this->onConsecutiveCalls(
               $data[2], $data[3]
             ));

        $coverage = new PHP_CodeCoverage($stub, new PHP_CodeCoverage_Filter);

        $coverage->start(
          new BankAccountTest('testBalanceCannotBecomeNegative2'), TRUE
        );
        $coverage->stop();

        $coverage->start(
          new BankAccountTest('testDepositWithdrawMoney')
        );
        $coverage->stop();

        return $coverage;
    }

    protected function getExpectedDataArrayForBankAccount()
    {
        return array(
          TEST_FILES_PATH . 'BankAccount.php' => array(
            8 => array(
              0 => 'BankAccountTest::testBalanceIsInitiallyZero',
              1 => 'BankAccountTest::testDepositWithdrawMoney'
            ),
            9 => NULL,
            13 => array(),
            14 => array(),
            15 => array(),
            16 => array(),
            18 => array(),
            22 => array(
              0 => 'BankAccountTest::testBalanceCannotBecomeNegative2',
              1 => 'BankAccountTest::testDepositWithdrawMoney'
            ),
            24 => array(
              0 => 'BankAccountTest::testDepositWithdrawMoney',
            ),
            25 => NULL,
            29 => array(
              0 => 'BankAccountTest::testBalanceCannotBecomeNegative',
              1 => 'BankAccountTest::testDepositWithdrawMoney'
            ),
            31 => array(
              0 => 'BankAccountTest::testDepositWithdrawMoney'
            ),
            32 => NULL
          )
        );
    }

    protected function getCoverageForFileWithIgnoredLines()
    {
        $coverage = new PHP_CodeCoverage(
          $this->setUpXdebugStubForFileWithIgnoredLines(),
          new PHP_CodeCoverage_Filter
        );

        $coverage->start('FileWithIgnoredLines', TRUE);
        $coverage->stop();

        return $coverage;
    }

    protected function setUpXdebugStubForFileWithIgnoredLines()
    {
        $stub = $this->getMock('PHP_CodeCoverage_Driver_Xdebug');
        $stub->expects($this->any())
             ->method('stop')
             ->will($this->returnValue(
               array(
                 TEST_FILES_PATH . 'source_with_ignore.php' => array(
                   2 => 1,
                   4 => -1,
                   6 => -1,
                   7 => 1
                 )
               )
            ));

        return $stub;
    }
}
