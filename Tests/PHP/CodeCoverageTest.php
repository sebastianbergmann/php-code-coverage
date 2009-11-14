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

require_once 'PHP/CodeCoverage.php';

if (!defined('TEST_FILES_PATH')) {
    define(
      'TEST_FILES_PATH',
      dirname(dirname(__FILE__)) . DIRECTORY_SEPARATOR .
      '_files' . DIRECTORY_SEPARATOR
    );
}

require_once TEST_FILES_PATH . 'BankAccount.php';
require_once TEST_FILES_PATH . 'BankAccountTest.php';

/**
 * Tests for the PHP_CodeCoverage class.
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
class PHP_CodeCoverageTest extends PHPUnit_Framework_TestCase
{
    /**
     * @covers PHP_CodeCoverage::__construct
     * @covers PHP_CodeCoverage::filter
     */
    public function testConstructor()
    {
        $coverage = new PHP_CodeCoverage;

        $this->assertAttributeType(
          'PHP_CodeCoverage_Driver_Xdebug', 'driver', $coverage
        );

        $this->assertType('PHP_CodeCoverage_Filter', $coverage->filter());
    }

    /**
     * @covers PHP_CodeCoverage::start
     * @covers PHP_CodeCoverage::stop
     * @covers PHP_CodeCoverage::append
     * @covers PHP_CodeCoverage::getLinesByStatus
     */
    public function testCollect()
    {
        $coverage = new PHP_CodeCoverage($this->setUpStub());

        $coverage->start(
          new BankAccountTest('testBalanceIsInitiallyZero'), TRUE
        );
        $coverage->stop();

        $coverage->start(new BankAccountTest(
          'testBalanceCannotBecomeNegative')
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

        $this->assertAttributeEquals(
          array(
            'BankAccountTest::testBalanceIsInitiallyZero' => array(
              'executed' => array(
                TEST_FILES_PATH . 'BankAccount.php' => array(
                  8 => 1
                )
              ),
              'dead' => array(
                TEST_FILES_PATH . 'BankAccount.php' => array(
                   9 => -2,
                  25 => -2,
                  32 => -2
                )
              ),
              'executable' => array(
                TEST_FILES_PATH . 'BankAccount.php' => array(
                   8 => 1,
                  13 => -1,
                  14 => -1,
                  15 => -1,
                  16 => -1,
                  18 => -1,
                  22 => -1,
                  24 => -1,
                  29 => -1,
                  31 => -1
                )
              )
            ),
            'BankAccountTest::testBalanceCannotBecomeNegative' => array(
              'executed' => array(
                TEST_FILES_PATH . 'BankAccount.php' => array(
                  29 => 1
                )
              ),
              'dead' => array(),
              'executable' => array()
            ),
            'BankAccountTest::testBalanceCannotBecomeNegative2' => array(
              'executed' => array(
                TEST_FILES_PATH . 'BankAccount.php' => array(
                  22 => 1
                )
              ),
              'dead' => array(),
              'executable' => array()
            ),
            'BankAccountTest::testDepositWithdrawMoney' => array(
              'executed' => array(
                TEST_FILES_PATH . 'BankAccount.php' => array(
                   8 => 1,
                  22 => 1,
                  24 => 1,
                  29 => 1,
                  31 => 1
                )
              ),
              'dead' => array(),
              'executable' => array()
            )
          ),
          'data',
          $coverage
        );

        return $coverage;
    }

    /**
     * @covers PHP_CodeCoverage::getSummary
     * @depends testCollect
     */
    public function testSummary(PHP_CodeCoverage $coverage)
    {
        $this->markTestIncomplete();

        $this->assertEquals(
          array(),
          $coverage->getSummary()
        );
    }

    protected function setUpStub()
    {
        $stub = $this->getMock('PHP_CodeCoverage_Driver_Xdebug');
        $stub->expects($this->any())
             ->method('stop')
             ->will($this->onConsecutiveCalls(
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
             ));

        return $stub;
    }
}
?>
