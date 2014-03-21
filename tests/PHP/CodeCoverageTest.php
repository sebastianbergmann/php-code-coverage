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
 * @author     Sebastian Bergmann <sebastian@phpunit.de>
 * @copyright  2009-2014 Sebastian Bergmann <sebastian@phpunit.de>
 * @license    http://www.opensource.org/licenses/BSD-3-Clause  The BSD 3-Clause License
 * @link       http://github.com/sebastianbergmann/php-code-coverage
 * @since      Class available since Release 1.0.0
 */
class PHP_CodeCoverageTest extends PHP_CodeCoverage_TestCase
{
    /**
     * @var PHP_CodeCoverage
     */
    private $coverage;

    protected function setUp()
    {
        $this->coverage = new PHP_CodeCoverage;
    }

    /**
     * @covers PHP_CodeCoverage::__construct
     * @covers PHP_CodeCoverage::filter
     */
    public function testConstructor()
    {
        $this->assertAttributeInstanceOf(
          'PHP_CodeCoverage_Driver', 'driver', $this->coverage
        );

        $this->assertAttributeInstanceOf(
          'PHP_CodeCoverage_Filter', 'filter', $this->coverage
        );
    }

    /**
     * @covers PHP_CodeCoverage::__construct
     * @covers PHP_CodeCoverage::filter
     */
    public function testConstructor2()
    {
        $filter   = new PHP_CodeCoverage_Filter;
        $coverage = new PHP_CodeCoverage(null, $filter);

        $this->assertAttributeInstanceOf(
          'PHP_CodeCoverage_Driver', 'driver', $coverage
        );

        $this->assertSame($filter, $coverage->filter());
    }

    /**
     * @covers            PHP_CodeCoverage::start
     * @expectedException PHP_CodeCoverage_Exception
     */
    public function testStartThrowsExceptionForInvalidArgument()
    {
        $this->coverage->start(null, array(), null);
    }

    /**
     * @covers            PHP_CodeCoverage::stop
     * @expectedException PHP_CodeCoverage_Exception
     */
    public function testStopThrowsExceptionForInvalidArgument()
    {
        $this->coverage->stop(null);
    }

    /**
     * @covers            PHP_CodeCoverage::stop
     * @expectedException PHP_CodeCoverage_Exception
     */
    public function testStopThrowsExceptionForInvalidArgument2()
    {
        $this->coverage->stop(true, null);
    }

    /**
     * @covers            PHP_CodeCoverage::append
     * @expectedException PHP_CodeCoverage_Exception
     */
    public function testAppendThrowsExceptionForInvalidArgument()
    {
        $this->coverage->append(array(), null);
    }

    /**
     * @covers            PHP_CodeCoverage::setCacheTokens
     * @expectedException PHP_CodeCoverage_Exception
     */
    public function testSetCacheTokensThrowsExceptionForInvalidArgument()
    {
        $this->coverage->setCacheTokens(null);
    }

    /**
     * @covers PHP_CodeCoverage::setCacheTokens
     */
    public function testSetCacheTokens()
    {
        $this->coverage->setCacheTokens(true);
        $this->assertAttributeEquals(true, 'cacheTokens', $this->coverage);
    }

    /**
     * @covers            PHP_CodeCoverage::setCheckForUnintentionallyCoveredCode
     * @expectedException PHP_CodeCoverage_Exception
     */
    public function testSetCheckForUnintentionallyCoveredCodeThrowsExceptionForInvalidArgument()
    {
        $this->coverage->setCheckForUnintentionallyCoveredCode(null);
    }

    /**
     * @covers PHP_CodeCoverage::setCheckForUnintentionallyCoveredCode
     */
    public function testSetCheckForUnintentionallyCoveredCode()
    {
        $this->coverage->setCheckForUnintentionallyCoveredCode(true);
        $this->assertAttributeEquals(
          true, 'checkForUnintentionallyCoveredCode', $this->coverage
        );
    }

    /**
     * @covers            PHP_CodeCoverage::setForceCoversAnnotation
     * @expectedException PHP_CodeCoverage_Exception
     */
    public function testSetForceCoversAnnotationThrowsExceptionForInvalidArgument()
    {
        $this->coverage->setForceCoversAnnotation(null);
    }

    /**
     * @covers PHP_CodeCoverage::setForceCoversAnnotation
     */
    public function testSetForceCoversAnnotation()
    {
        $this->coverage->setForceCoversAnnotation(true);
        $this->assertAttributeEquals(
          true, 'forceCoversAnnotation', $this->coverage
        );
    }

    /**
     * @covers            PHP_CodeCoverage::setAddUncoveredFilesFromWhitelist
     * @expectedException PHP_CodeCoverage_Exception
     */
    public function testSetAddUncoveredFilesFromWhitelistThrowsExceptionForInvalidArgument()
    {
        $this->coverage->setAddUncoveredFilesFromWhitelist(null);
    }

    /**
     * @covers PHP_CodeCoverage::setAddUncoveredFilesFromWhitelist
     */
    public function testSetAddUncoveredFilesFromWhitelist()
    {
        $this->coverage->setAddUncoveredFilesFromWhitelist(true);
        $this->assertAttributeEquals(
          true, 'addUncoveredFilesFromWhitelist', $this->coverage
        );
    }

    /**
     * @covers            PHP_CodeCoverage::setProcessUncoveredFilesFromWhitelist
     * @expectedException PHP_CodeCoverage_Exception
     */
    public function testSetProcessUncoveredFilesFromWhitelistThrowsExceptionForInvalidArgument()
    {
        $this->coverage->setProcessUncoveredFilesFromWhitelist(null);
    }

    /**
     * @covers PHP_CodeCoverage::setProcessUncoveredFilesFromWhitelist
     */
    public function testSetProcessUncoveredFilesFromWhitelist()
    {
        $this->coverage->setProcessUncoveredFilesFromWhitelist(true);
        $this->assertAttributeEquals(
          true, 'processUncoveredFilesFromWhitelist', $this->coverage
        );
    }

    /**
     * @covers PHP_CodeCoverage::setMapTestClassNameToCoveredClassName
     */
    public function testSetMapTestClassNameToCoveredClassName()
    {
        $this->coverage->setMapTestClassNameToCoveredClassName(true);
        $this->assertAttributeEquals(
          true, 'mapTestClassNameToCoveredClassName', $this->coverage
        );
    }

    /**
     * @covers            PHP_CodeCoverage::setMapTestClassNameToCoveredClassName
     * @expectedException PHP_CodeCoverage_Exception
     */
    public function testSetMapTestClassNameToCoveredClassNameThrowsExceptionForInvalidArgument()
    {
        $this->coverage->setMapTestClassNameToCoveredClassName(null);
    }

    /**
     * @covers PHP_CodeCoverage::clear
     */
    public function testClear()
    {
        $this->coverage->clear();

        $this->assertAttributeEquals(null, 'currentId', $this->coverage);
        $this->assertAttributeEquals(array(), 'data', $this->coverage);
        $this->assertAttributeEquals(array(), 'tests', $this->coverage);
    }

    /**
     * @covers PHP_CodeCoverage::start
     * @covers PHP_CodeCoverage::stop
     * @covers PHP_CodeCoverage::append
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
            'BankAccountTest::testBalanceIsInitiallyZero' => null,
            'BankAccountTest::testBalanceCannotBecomeNegative' => null,
            'BankAccountTest::testBalanceCannotBecomeNegative2' => null,
            'BankAccountTest::testDepositWithdrawMoney' => null
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
        $driver = $this->getMockBuilder('PHP_CodeCoverage_Driver')
                       ->setConstructorArgs(array(new PHP_CodeCoverage_Filter, new PHP_CodeCoverage_Parser))
                       ->getMockForAbstractClass();

        $coverage = new PHP_CodeCoverage($driver, new PHP_CodeCoverage_Filter);

        $coverage->merge($this->getCoverageForBankAccount());

        $this->assertEquals(
          $this->getExpectedDataArrayForBankAccount(), $coverage->getData()
        );
    }
}
