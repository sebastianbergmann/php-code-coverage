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
 * @license    http://www.opensource.org/licenses/bsd-license.php  BSD License
 * @link       http://github.com/sebastianbergmann/php-code-coverage
 * @since      File available since Release 1.0.0
 */

if (!defined('TEST_FILES_PATH')) {
    define(
      'TEST_FILES_PATH',
      dirname(dirname(__FILE__)) . DIRECTORY_SEPARATOR .
      '_files' . DIRECTORY_SEPARATOR
    );
}

require_once TEST_FILES_PATH . '../TestCase.php';

require_once TEST_FILES_PATH . 'BankAccount.php';
require_once TEST_FILES_PATH . 'BankAccountTest.php';

/**
 * Tests for the PHP_CodeCoverage class.
 *
 * @category   PHP
 * @package    CodeCoverage
 * @subpackage Tests
 * @author     Sebastian Bergmann <sb@sebastian-bergmann.de>
 * @copyright  2009-2012 Sebastian Bergmann <sb@sebastian-bergmann.de>
 * @license    http://www.opensource.org/licenses/bsd-license.php  BSD License
 * @version    Release: @package_version@
 * @link       http://github.com/sebastianbergmann/php-code-coverage
 * @since      Class available since Release 1.0.0
 */
class PHP_CodeCoverageTest extends PHP_CodeCoverage_TestCase
{
    /**
     * @covers PHP_CodeCoverage::__construct
     * @covers PHP_CodeCoverage::filter
     */
    public function testConstructor()
    {
        $coverage = new PHP_CodeCoverage;

        $this->assertAttributeInstanceOf(
          'PHP_CodeCoverage_Driver_Xdebug', 'driver', $coverage
        );

        $this->assertAttributeInstanceOf(
          'PHP_CodeCoverage_Filter', 'filter', $coverage
        );
    }

    /**
     * @covers PHP_CodeCoverage::__construct
     * @covers PHP_CodeCoverage::filter
     */
    public function testConstructor2()
    {
        $filter   = new PHP_CodeCoverage_Filter;
        $coverage = new PHP_CodeCoverage(NULL, $filter);

        $this->assertAttributeInstanceOf(
          'PHP_CodeCoverage_Driver_Xdebug', 'driver', $coverage
        );

        $this->assertSame($filter, $coverage->filter());
    }

    /**
     * @covers            PHP_CodeCoverage::start
     * @expectedException PHP_CodeCoverage_Exception
     */
    public function testStartThrowsExceptionForInvalidArgument()
    {
        $coverage = new PHP_CodeCoverage;
        $coverage->start(NULL, array(), NULL);
    }

    /**
     * @covers            PHP_CodeCoverage::stop
     * @expectedException PHP_CodeCoverage_Exception
     */
    public function testStopThrowsExceptionForInvalidArgument()
    {
        $coverage = new PHP_CodeCoverage;
        $coverage->stop(NULL);
    }

    /**
     * @covers            PHP_CodeCoverage::append
     * @expectedException PHP_CodeCoverage_Exception
     */
    public function testAppendThrowsExceptionForInvalidArgument()
    {
        $coverage = new PHP_CodeCoverage;
        $coverage->append(array(), NULL);
    }

    /**
     * @covers            PHP_CodeCoverage::setCacheTokens
     * @expectedException PHP_CodeCoverage_Exception
     */
    public function testSetCacheTokensThrowsExceptionForInvalidArgument()
    {
        $coverage = new PHP_CodeCoverage;
        $coverage->setCacheTokens(NULL);
    }

    /**
     * @covers PHP_CodeCoverage::setCacheTokens
     */
    public function testSetCacheTokens()
    {
        $coverage = new PHP_CodeCoverage;
        $coverage->setCacheTokens(TRUE);
        $this->assertAttributeEquals(TRUE, 'cacheTokens', $coverage);
    }

    /**
     * @covers            PHP_CodeCoverage::setForceCoversAnnotation
     * @expectedException PHP_CodeCoverage_Exception
     */
    public function testSetForceCoversAnnotationThrowsExceptionForInvalidArgument()
    {
        $coverage = new PHP_CodeCoverage;
        $coverage->setForceCoversAnnotation(NULL);
    }

    /**
     * @covers PHP_CodeCoverage::setForceCoversAnnotation
     */
    public function testSetForceCoversAnnotation()
    {
        $coverage = new PHP_CodeCoverage;
        $coverage->setForceCoversAnnotation(TRUE);
        $this->assertAttributeEquals(TRUE, 'forceCoversAnnotation', $coverage);
    }

    /**
     * @covers            PHP_CodeCoverage::setProcessUncoveredFilesFromWhitelist
     * @expectedException PHP_CodeCoverage_Exception
     */
    public function testSetProcessUncoveredFilesFromWhitelistThrowsExceptionForInvalidArgument()
    {
        $coverage = new PHP_CodeCoverage;
        $coverage->setProcessUncoveredFilesFromWhitelist(NULL);
    }

    /**
     * @covers PHP_CodeCoverage::setProcessUncoveredFilesFromWhitelist
     */
    public function testSetProcessUncoveredFilesFromWhitelist()
    {
        $coverage = new PHP_CodeCoverage;
        $coverage->setProcessUncoveredFilesFromWhitelist(TRUE);
        $this->assertAttributeEquals(
          TRUE, 'processUncoveredFilesFromWhitelist', $coverage
        );
    }

    /**
     * @covers PHP_CodeCoverage::setMapTestClassNameToCoveredClassName
     */
    public function testSetMapTestClassNameToCoveredClassName()
    {
        $coverage = new PHP_CodeCoverage;
        $coverage->setMapTestClassNameToCoveredClassName(TRUE);
        $this->assertAttributeEquals(
          TRUE, 'mapTestClassNameToCoveredClassName', $coverage
        );
    }

    /**
     * @covers            PHP_CodeCoverage::setMapTestClassNameToCoveredClassName
     * @expectedException PHP_CodeCoverage_Exception
     */
    public function testSetMapTestClassNameToCoveredClassNameThrowsExceptionForInvalidArgument()
    {
        $coverage = new PHP_CodeCoverage;
        $coverage->setMapTestClassNameToCoveredClassName(NULL);
    }

    /**
     * @covers PHP_CodeCoverage::clear
     */
    public function testClear()
    {
        $coverage = new PHP_CodeCoverage;
        $coverage->clear();

        $this->assertAttributeEquals(NULL, 'currentId', $coverage);
        $this->assertAttributeEquals(array(), 'data', $coverage);
        $this->assertAttributeEquals(array(), 'tests', $coverage);
    }

    /**
     * @covers PHP_CodeCoverage::start
     * @covers PHP_CodeCoverage::stop
     * @covers PHP_CodeCoverage::append
     * @covers PHP_CodeCoverage::applyListsFilter
     * @covers PHP_CodeCoverage::initializeFilesThatAreSeenTheFirstTime
     * @covers PHP_CodeCoverage::applyCoversAnnotationFilter
     * @covers PHP_CodeCoverage::getTests
     */
    public function testCollect()
    {
        $coverage = $this->getCoverageForBankAccount();

        $this->assertEquals(
          $this->getExpectedDataArrayForBankAccount(), $coverage->getData()
        );

        $this->assertEquals(
          array(
            'BankAccountTest::testBalanceIsInitiallyZero' => NULL,
            'BankAccountTest::testBalanceCannotBecomeNegative' => NULL,
            'BankAccountTest::testBalanceCannotBecomeNegative2' => NULL,
            'BankAccountTest::testDepositWithdrawMoney' => NULL
          ),
          $coverage->getTests()
        );
    }

    /**
     * @covers PHP_CodeCoverage::getData
     * @covers PHP_CodeCoverage::merge
     */
    public function testMerge()
    {
        $coverage = $this->getCoverageForBankAccountForFirstTwoTests();
        $coverage->merge($this->getCoverageForBankAccountForLastTwoTests());

        $this->assertEquals(
          $this->getExpectedDataArrayForBankAccount(), $coverage->getData()
        );
    }

    /**
     * @covers PHP_CodeCoverage::getData
     * @covers PHP_CodeCoverage::merge
     */
    public function testMerge2()
    {
        $coverage = new PHP_CodeCoverage(
          $this->getMock('PHP_CodeCoverage_Driver_Xdebug'),
          new PHP_CodeCoverage_Filter
        );

        $coverage->merge($this->getCoverageForBankAccount());

        $this->assertEquals(
          $this->getExpectedDataArrayForBankAccount(), $coverage->getData()
        );
    }
}
