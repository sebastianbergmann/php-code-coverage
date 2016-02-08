<?php
/*
 * This file is part of the PHP_CodeCoverage package.
 *
 * (c) Sebastian Bergmann <sebastian@phpunit.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

require_once dirname(dirname(__FILE__)) . DIRECTORY_SEPARATOR . 'TestCase.php';

/**
 * Tests for the PHP_CodeCoverage class.
 *
 * @since Class available since Release 1.0.0
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
            'PHP_CodeCoverage_Driver_Xdebug',
            'driver',
            $this->coverage
        );

        $this->assertAttributeInstanceOf(
            'PHP_CodeCoverage_Filter',
            'filter',
            $this->coverage
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
            'PHP_CodeCoverage_Driver_Xdebug',
            'driver',
            $coverage
        );

        $this->assertSame($filter, $coverage->filter());
    }

    /**
     * @covers            PHP_CodeCoverage::start
     * @expectedException PHP_CodeCoverage_Exception
     */
    public function testStartThrowsExceptionForInvalidArgument()
    {
        $this->coverage->start(null, [], null);
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
        $this->coverage->append([], null);
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
            true,
            'checkForUnintentionallyCoveredCode',
            $this->coverage
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
     * @covers PHP_CodeCoverage::setCheckForMissingCoversAnnotation
     */
    public function testSetCheckForMissingCoversAnnotation()
    {
        $this->coverage->setCheckForMissingCoversAnnotation(true);
        $this->assertAttributeEquals(
            true,
            'checkForMissingCoversAnnotation',
            $this->coverage
        );
    }

    /**
     * @covers            PHP_CodeCoverage::setCheckForMissingCoversAnnotation
     * @expectedException PHP_CodeCoverage_Exception
     */
    public function testSetCheckForMissingCoversAnnotationThrowsExceptionForInvalidArgument()
    {
        $this->coverage->setCheckForMissingCoversAnnotation(null);
    }

    /**
     * @covers PHP_CodeCoverage::setForceCoversAnnotation
     */
    public function testSetForceCoversAnnotation()
    {
        $this->coverage->setForceCoversAnnotation(true);
        $this->assertAttributeEquals(
            true,
            'forceCoversAnnotation',
            $this->coverage
        );
    }

    /**
     * @covers            PHP_CodeCoverage::setCheckForUnexecutedCoveredCode
     * @expectedException PHP_CodeCoverage_Exception
     */
    public function testSetCheckForUnexecutedCoveredCodeThrowsExceptionForInvalidArgument()
    {
        $this->coverage->setCheckForUnexecutedCoveredCode(null);
    }

    /**
     * @covers PHP_CodeCoverage::setCheckForUnexecutedCoveredCode
     */
    public function testSetCheckForUnexecutedCoveredCode()
    {
        $this->coverage->setCheckForUnexecutedCoveredCode(true);
        $this->assertAttributeEquals(
            true,
            'checkForUnexecutedCoveredCode',
            $this->coverage
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
            true,
            'addUncoveredFilesFromWhitelist',
            $this->coverage
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
            true,
            'processUncoveredFilesFromWhitelist',
            $this->coverage
        );
    }

    /**
     * @covers PHP_CodeCoverage::setIgnoreDeprecatedCode
     */
    public function testSetIgnoreDeprecatedCode()
    {
        $this->coverage->setIgnoreDeprecatedCode(true);
        $this->assertAttributeEquals(
            true,
            'ignoreDeprecatedCode',
            $this->coverage
        );
    }

    /**
     * @covers            PHP_CodeCoverage::setIgnoreDeprecatedCode
     * @expectedException PHP_CodeCoverage_Exception
     */
    public function testSetIgnoreDeprecatedCodeThrowsExceptionForInvalidArgument()
    {
        $this->coverage->setIgnoreDeprecatedCode(null);
    }

    /**
     * @covers PHP_CodeCoverage::clear
     */
    public function testClear()
    {
        $this->coverage->clear();

        $this->assertAttributeEquals(null, 'currentId', $this->coverage);
        $this->assertAttributeEquals([], 'data', $this->coverage);
        $this->assertAttributeEquals([], 'tests', $this->coverage);
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
            $this->getExpectedDataArrayForBankAccount(),
            $coverage->getData()
        );

        if (version_compare(PHPUnit_Runner_Version::id(), '4.7', '>=')) {
            $size = 'unknown';
        } else {
            $size = 'small';
        }

        $this->assertEquals(
            [
                'BankAccountTest::testBalanceIsInitiallyZero'       => ['size' => $size, 'status' => null],
                'BankAccountTest::testBalanceCannotBecomeNegative'  => ['size' => $size, 'status' => null],
                'BankAccountTest::testBalanceCannotBecomeNegative2' => ['size' => $size, 'status' => null],
                'BankAccountTest::testDepositWithdrawMoney'         => ['size' => $size, 'status' => null]
            ],
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
            $this->getExpectedDataArrayForBankAccount(),
            $coverage->getData()
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
            $this->getExpectedDataArrayForBankAccount(),
            $coverage->getData()
        );
    }

    /**
     * @covers PHP_CodeCoverage::getLinesToBeIgnored
     */
    public function testGetLinesToBeIgnored()
    {
        $this->assertEquals(
            [
                1,
                3,
                4,
                5,
                7,
                8,
                9,
                10,
                11,
                12,
                13,
                14,
                15,
                16,
                17,
                18,
                19,
                20,
                21,
                22,
                23,
                24,
                25,
                26,
                27,
                28,
                30,
                32,
                33,
                34,
                35,
                36,
                37,
                38
            ],
            $this->getLinesToBeIgnored()->invoke(
                $this->coverage,
                TEST_FILES_PATH . 'source_with_ignore.php'
            )
        );
    }

    /**
     * @covers PHP_CodeCoverage::getLinesToBeIgnored
     */
    public function testGetLinesToBeIgnored2()
    {
        $this->assertEquals(
            [1, 5],
            $this->getLinesToBeIgnored()->invoke(
                $this->coverage,
                TEST_FILES_PATH . 'source_without_ignore.php'
            )
        );
    }

    /**
     * @covers PHP_CodeCoverage::getLinesToBeIgnored
     */
    public function testGetLinesToBeIgnored3()
    {
        $this->assertEquals(
            [
                1,
                2,
                3,
                4,
                5,
                8,
                11,
                15,
                16,
                19,
                20
            ],
            $this->getLinesToBeIgnored()->invoke(
                $this->coverage,
                TEST_FILES_PATH . 'source_with_class_and_anonymous_function.php'
            )
        );
    }

    /**
     * @covers PHP_CodeCoverage::getLinesToBeIgnored
     */
    public function testGetLinesToBeIgnoredOneLineAnnotations()
    {
        $this->assertEquals(
            [
                1,
                2,
                3,
                4,
                5,
                6,
                7,
                8,
                9,
                10,
                11,
                12,
                13,
                14,
                15,
                16,
                18,
                20,
                21,
                23,
                24,
                25,
                27,
                28,
                29,
                30,
                31,
                32,
                33,
                34,
                37
            ],
            $this->getLinesToBeIgnored()->invoke(
                $this->coverage,
                TEST_FILES_PATH . 'source_with_oneline_annotations.php'
            )
        );
    }

    /**
     * @return ReflectionMethod
     */
    private function getLinesToBeIgnored()
    {
        $getLinesToBeIgnored = new ReflectionMethod(
            'PHP_CodeCoverage',
            'getLinesToBeIgnored'
        );

        $getLinesToBeIgnored->setAccessible(true);

        return $getLinesToBeIgnored;
    }

    /**
     * @covers PHP_CodeCoverage::getLinesToBeIgnored
     */
    public function testGetLinesToBeIgnoredWhenIgnoreIsDisabled()
    {
        $this->coverage->setDisableIgnoredLines(true);

        $this->assertEquals(
            [],
            $this->getLinesToBeIgnored()->invoke(
                $this->coverage,
                TEST_FILES_PATH . 'source_with_ignore.php'
            )
        );
    }

    /**
     * @covers PHP_CodeCoverage::performUnexecutedCoveredCodeCheck
     * @expectedException PHP_CodeCoverage_CoveredCodeNotExecutedException
     */
    public function testAppendThrowsExceptionIfCoveredCodeWasNotExecuted()
    {
        $this->coverage->filter()->addDirectoryToWhitelist(TEST_FILES_PATH);
        $this->coverage->setCheckForUnexecutedCoveredCode(true);
        $data = [
            TEST_FILES_PATH . 'BankAccount.php' => [
                29 => -1,
                31 => -1
            ]
        ];
        $linesToBeCovered = [
            TEST_FILES_PATH . 'BankAccount.php' => [
                22,
                24
            ]
        ];
        $linesToBeUsed = [];

        $this->coverage->append($data, 'File1.php', true, $linesToBeCovered, $linesToBeUsed);
    }

    /**
     * @covers PHP_CodeCoverage::performUnexecutedCoveredCodeCheck
     * @expectedException PHP_CodeCoverage_CoveredCodeNotExecutedException
     */
    public function testAppendThrowsExceptionIfUsedCodeWasNotExecuted()
    {
        $this->coverage->filter()->addDirectoryToWhitelist(TEST_FILES_PATH);
        $this->coverage->setCheckForUnexecutedCoveredCode(true);
        $data = [
            TEST_FILES_PATH . 'BankAccount.php' => [
                29 => -1,
                31 => -1
            ]
        ];
        $linesToBeCovered = [
            TEST_FILES_PATH . 'BankAccount.php' => [
                29,
                31
            ]
        ];
        $linesToBeUsed = [
            TEST_FILES_PATH . 'BankAccount.php' => [
                22,
                24
            ]
        ];

        $this->coverage->append($data, 'File1.php', true, $linesToBeCovered, $linesToBeUsed);
    }
}
