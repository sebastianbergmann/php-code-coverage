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
    protected $coverage;
    protected $getLinesToBeCovered;

    protected function setUp()
    {
        $this->coverage = new PHP_CodeCoverage;

        $this->getLinesToBeCovered = new ReflectionMethod(
          'PHP_CodeCoverage', 'getLinesToBeCovered'
        );

        $this->getLinesToBeCovered->setAccessible(TRUE);
    }

    /**
     * @covers PHP_CodeCoverage::__construct
     * @covers PHP_CodeCoverage::filter
     */
    public function testConstructor()
    {
        $this->assertAttributeInstanceOf(
          'PHP_CodeCoverage_Driver_Xdebug', 'driver', $this->coverage
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
        $this->coverage->start(NULL, array(), NULL);
    }

    /**
     * @covers            PHP_CodeCoverage::stop
     * @expectedException PHP_CodeCoverage_Exception
     */
    public function testStopThrowsExceptionForInvalidArgument()
    {
        $this->coverage->stop(NULL);
    }

    /**
     * @covers            PHP_CodeCoverage::append
     * @expectedException PHP_CodeCoverage_Exception
     */
    public function testAppendThrowsExceptionForInvalidArgument()
    {
        $this->coverage->append(array(), NULL);
    }

    /**
     * @covers            PHP_CodeCoverage::setCacheTokens
     * @expectedException PHP_CodeCoverage_Exception
     */
    public function testSetCacheTokensThrowsExceptionForInvalidArgument()
    {
        $this->coverage->setCacheTokens(NULL);
    }

    /**
     * @covers PHP_CodeCoverage::setCacheTokens
     */
    public function testSetCacheTokens()
    {
        $this->coverage->setCacheTokens(TRUE);
        $this->assertAttributeEquals(TRUE, 'cacheTokens', $this->coverage);
    }

    /**
     * @covers            PHP_CodeCoverage::setForceCoversAnnotation
     * @expectedException PHP_CodeCoverage_Exception
     */
    public function testSetForceCoversAnnotationThrowsExceptionForInvalidArgument()
    {
        $this->coverage->setForceCoversAnnotation(NULL);
    }

    /**
     * @covers PHP_CodeCoverage::setForceCoversAnnotation
     */
    public function testSetForceCoversAnnotation()
    {
        $this->coverage->setForceCoversAnnotation(TRUE);
        $this->assertAttributeEquals(
          TRUE, 'forceCoversAnnotation', $this->coverage
        );
    }

    /**
     * @covers            PHP_CodeCoverage::setProcessUncoveredFilesFromWhitelist
     * @expectedException PHP_CodeCoverage_Exception
     */
    public function testSetProcessUncoveredFilesFromWhitelistThrowsExceptionForInvalidArgument()
    {
        $this->coverage->setProcessUncoveredFilesFromWhitelist(NULL);
    }

    /**
     * @covers PHP_CodeCoverage::setProcessUncoveredFilesFromWhitelist
     */
    public function testSetProcessUncoveredFilesFromWhitelist()
    {
        $this->coverage->setProcessUncoveredFilesFromWhitelist(TRUE);
        $this->assertAttributeEquals(
          TRUE, 'processUncoveredFilesFromWhitelist', $this->coverage
        );
    }

    /**
     * @covers PHP_CodeCoverage::setMapTestClassNameToCoveredClassName
     */
    public function testSetMapTestClassNameToCoveredClassName()
    {
        $this->coverage->setMapTestClassNameToCoveredClassName(TRUE);
        $this->assertAttributeEquals(
          TRUE, 'mapTestClassNameToCoveredClassName', $this->coverage
        );
    }

    /**
     * @covers            PHP_CodeCoverage::setMapTestClassNameToCoveredClassName
     * @expectedException PHP_CodeCoverage_Exception
     */
    public function testSetMapTestClassNameToCoveredClassNameThrowsExceptionForInvalidArgument()
    {
        $this->coverage->setMapTestClassNameToCoveredClassName(NULL);
    }

    /**
     * @covers PHP_CodeCoverage::clear
     */
    public function testClear()
    {
        $this->coverage->clear();

        $this->assertAttributeEquals(NULL, 'currentId', $this->coverage);
        $this->assertAttributeEquals(array(), 'data', $this->coverage);
        $this->assertAttributeEquals(array(), 'tests', $this->coverage);
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

    /**
     * @covers       PHP_CodeCoverage::getLinesToBeCovered
     * @covers       PHP_CodeCoverage::resolveCoversToReflectionObjects
     * @dataProvider getLinesToBeCoveredProvider
     */
    public function testGetLinesToBeCovered($test, $lines)
    {
        if (strpos($test, 'Namespace') === 0) {
            $expected = array(
              TEST_FILES_PATH . 'NamespaceCoveredClass.php' => $lines
            );
        }

        else if ($test === 'CoverageNoneTest') {
            $expected = array();
        }

        else if ($test === 'CoverageNothingTest') {
            $expected = false;
        }

        else if ($test === 'CoverageFunctionTest') {
            $expected = array(
              TEST_FILES_PATH . 'CoveredFunction.php' => $lines
            );
        }

        else {
            $expected = array(TEST_FILES_PATH . 'CoveredClass.php' => $lines);
        }

        $this->assertEquals(
          $expected,
          $this->getLinesToBeCovered->invoke(
            $this->coverage, $test, 'testSomething'
          )
        );
    }

    /**
     * @covers            PHP_CodeCoverage::getLinesToBeCovered
     * @covers            PHP_CodeCoverage::resolveCoversToReflectionObjects
     * @expectedException PHP_CodeCoverage_Exception
     */
    public function testGetLinesToBeCovered2()
    {
        $this->getLinesToBeCovered->invoke(
          $this->coverage, 'NotExistingCoveredElementTest', 'testOne'
        );
    }

    /**
     * @covers            PHP_CodeCoverage::getLinesToBeCovered
     * @covers            PHP_CodeCoverage::resolveCoversToReflectionObjects
     * @expectedException PHP_CodeCoverage_Exception
     */
    public function testGetLinesToBeCovered3()
    {
        $this->getLinesToBeCovered->invoke(
          $this->coverage, 'NotExistingCoveredElementTest', 'testTwo'
        );
    }

    /**
     * @covers            PHP_CodeCoverage::getLinesToBeCovered
     * @covers            PHP_CodeCoverage::resolveCoversToReflectionObjects
     * @expectedException PHP_CodeCoverage_Exception
     */
    public function testGetLinesToBeCovered4()
    {
        $this->getLinesToBeCovered->invoke(
          $this->coverage, 'NotExistingCoveredElementTest', 'testThree'
        );
    }

    /**
     * @covers PHP_CodeCoverage::getLinesToBeCovered
     */
    public function testGetLinesToBeCoveredSkipsNonExistantMethods()
    {
        $this->assertSame(
          array(),
          $this->getLinesToBeCovered->invoke(
            $this->coverage,
            'NotExistingCoveredElementTest',
            'methodDoesNotExist'
          )
        );
    }

    /**
     * @covers PHP_CodeCoverage::getLinesToBeCovered
     * @expectedException PHP_CodeCoverage_Exception
     */
    public function testTwoCoversDefaultClassAnnoationsAreNotAllowed()
    {
        $this->getLinesToBeCovered->invoke(
          $this->coverage,
          'CoverageTwoDefaultClassAnnotations',
          'testSomething'
        );
    }

    /**
     * @covers PHP_CodeCoverage::getLinesToBeCovered
     */
    public function testFunctionParenthesesAreAllowed()
    {
        $this->assertSame(
          array(TEST_FILES_PATH . 'CoveredFunction.php' => range(2, 4)),
          $this->getLinesToBeCovered->invoke(
            $this->coverage,
            'CoverageFunctionParenthesesTest',
            'testSomething'
          )
        );
    }

    /**
     * @covers PHP_CodeCoverage::getLinesToBeCovered
     */
    public function testFunctionParenthesesAreAllowedWithWhitespace()
    {
        $this->assertSame(
          array(TEST_FILES_PATH . 'CoveredFunction.php' => range(2, 4)),
          $this->getLinesToBeCovered->invoke(
            $this->coverage,
            'CoverageFunctionParenthesesWhitespaceTest',
            'testSomething'
          )
        );
    }

    /**
     * @covers PHP_CodeCoverage::getLinesToBeCovered
     */
    public function testMethodParenthesesAreAllowed()
    {
        $this->assertSame(
          array(TEST_FILES_PATH . 'CoveredClass.php' => range(31, 35)),
          $this->getLinesToBeCovered->invoke(
            $this->coverage,
            'CoverageMethodParenthesesTest',
            'testSomething'
          )
        );
    }

    /**
     * @covers PHP_CodeCoverage::getLinesToBeCovered
     */
    public function testMethodParenthesesAreAllowedWithWhitespace()
    {
        $this->assertSame(
          array(TEST_FILES_PATH . 'CoveredClass.php' => range(31, 35)),
          $this->getLinesToBeCovered->invoke(
            $this->coverage,
            'CoverageMethodParenthesesWhitespaceTest',
            'testSomething'
          )
        );
    }

    public function getLinesToBeCoveredProvider()
    {
        return array(
          array(
            'CoverageNoneTest',
            array()
          ),
          array(
            'CoverageClassExtendedTest',
            array_merge(range(19, 36), range(2, 17))
          ),
          array(
            'CoverageClassTest',
            range(19, 36)
          ),
          array(
            'CoverageMethodTest',
            range(31, 35)
          ),
          array(
            'CoverageMethodOneLineAnnotationTest',
            range(31, 35)
          ),
          array(
            'CoverageNotPrivateTest',
            array_merge(range(25, 29), range(31, 35))
          ),
          array(
            'CoverageNotProtectedTest',
            array_merge(range(21, 23), range(31, 35))
          ),
          array(
            'CoverageNotPublicTest',
            array_merge(range(21, 23), range(25, 29))
          ),
          array(
            'CoveragePrivateTest',
            range(21, 23)
          ),
          array(
            'CoverageProtectedTest',
            range(25, 29)
          ),
          array(
            'CoveragePublicTest',
            range(31, 35)
          ),
          array(
            'CoverageFunctionTest',
            range(2, 4)
          ),
          array(
            'NamespaceCoverageClassExtendedTest',
            array_merge(range(21, 38), range(4, 19))
          ),
          array(
            'NamespaceCoverageClassTest',
            range(21, 38)
          ),
          array(
            'NamespaceCoverageMethodTest',
            range(33, 37)
          ),
          array(
            'NamespaceCoverageNotPrivateTest',
            array_merge(range(27, 31), range(33, 37))
          ),
          array(
            'NamespaceCoverageNotProtectedTest',
            array_merge(range(23, 25), range(33, 37))
          ),
          array(
            'NamespaceCoverageNotPublicTest',
            array_merge(range(23, 25), range(27, 31))
          ),
          array(
            'NamespaceCoveragePrivateTest',
            range(23, 25)
          ),
          array(
            'NamespaceCoverageProtectedTest',
            range(27, 31)
          ),
          array(
            'NamespaceCoveragePublicTest',
            range(33, 37)
          ),
          array(
            'NamespaceCoverageCoversClassTest',
            array_merge(range(23, 25), range(27, 31), range(33, 37), range(6, 8), range(10, 13), range(15, 18))
          ),
          array(
            'NamespaceCoverageCoversClassPublicTest',
            range(33, 37)
          ),
          array(
            'CoverageNothingTest',
            false
          )
        );
    }
}
