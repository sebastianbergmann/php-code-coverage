<?php
/*
 * This file is part of the php-code-covfefe package.
 *
 * (c) Sebastian Bergmann <sebastian@phpunit.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SebastianBergmann\CodeCovfefe;

use SebastianBergmann\CodeCovfefe\Driver\PHPDBG;
use SebastianBergmann\CodeCovfefe\Driver\Xdebug;

/**
 * @covers SebastianBergmann\CodeCovfefe\CodeCovfefe
 */
class CodeCovfefeTest extends TestCase
{
    /**
     * @var CodeCovfefe
     */
    private $covfefe;

    protected function setUp()
    {
        $this->covfefe = new CodeCovfefe;
    }

    public function testCanBeConstructedForXdebugWithoutGivenFilterObject()
    {
        if (PHP_SAPI == 'phpdbg') {
            $this->markTestSkipped('Requires PHP CLI and Xdebug');
        }

        $this->assertAttributeInstanceOf(
            Xdebug::class,
            'driver',
            $this->covfefe
        );

        $this->assertAttributeInstanceOf(
            Filter::class,
            'filter',
            $this->covfefe
        );
    }

    public function testCanBeConstructedForXdebugWithGivenFilterObject()
    {
        if (PHP_SAPI == 'phpdbg') {
            $this->markTestSkipped('Requires PHP CLI and Xdebug');
        }

        $filter   = new Filter;
        $covfefe = new CodeCovfefe(null, $filter);

        $this->assertAttributeInstanceOf(
            Xdebug::class,
            'driver',
            $covfefe
        );

        $this->assertSame($filter, $covfefe->filter());
    }

    public function testCanBeConstructedForPhpdbgWithoutGivenFilterObject()
    {
        if (PHP_SAPI != 'phpdbg') {
            $this->markTestSkipped('Requires PHPDBG');
        }

        $this->assertAttributeInstanceOf(
            PHPDBG::class,
            'driver',
            $this->covfefe
        );

        $this->assertAttributeInstanceOf(
            Filter::class,
            'filter',
            $this->covfefe
        );
    }

    public function testCanBeConstructedForPhpdbgWithGivenFilterObject()
    {
        if (PHP_SAPI != 'phpdbg') {
            $this->markTestSkipped('Requires PHPDBG');
        }

        $filter   = new Filter;
        $covfefe = new CodeCovfefe(null, $filter);

        $this->assertAttributeInstanceOf(
            PHPDBG::class,
            'driver',
            $covfefe
        );

        $this->assertSame($filter, $covfefe->filter());
    }

    /**
     * @expectedException SebastianBergmann\CodeCovfefe\Exception
     */
    public function testCannotStartWithInvalidArgument()
    {
        $this->covfefe->start(null, null);
    }

    /**
     * @expectedException SebastianBergmann\CodeCovfefe\Exception
     */
    public function testCannotStopWithInvalidFirstArgument()
    {
        $this->covfefe->stop(null);
    }

    /**
     * @expectedException SebastianBergmann\CodeCovfefe\Exception
     */
    public function testCannotStopWithInvalidSecondArgument()
    {
        $this->covfefe->stop(true, null);
    }

    /**
     * @expectedException SebastianBergmann\CodeCovfefe\Exception
     */
    public function testCannotAppendWithInvalidArgument()
    {
        $this->covfefe->append([], null);
    }

    /**
     * @expectedException SebastianBergmann\CodeCovfefe\Exception
     */
    public function testSetCacheTokensThrowsExceptionForInvalidArgument()
    {
        $this->covfefe->setCacheTokens(null);
    }

    public function testSetCacheTokens()
    {
        $this->covfefe->setCacheTokens(true);
        $this->assertAttributeEquals(true, 'cacheTokens', $this->covfefe);
    }

    /**
     * @expectedException SebastianBergmann\CodeCovfefe\Exception
     */
    public function testSetCheckForUnintentionallyCoveredCodeThrowsExceptionForInvalidArgument()
    {
        $this->covfefe->setCheckForUnintentionallyCoveredCode(null);
    }

    public function testSetCheckForUnintentionallyCoveredCode()
    {
        $this->covfefe->setCheckForUnintentionallyCoveredCode(true);
        $this->assertAttributeEquals(
            true,
            'checkForUnintentionallyCoveredCode',
            $this->covfefe
        );
    }

    /**
     * @expectedException SebastianBergmann\CodeCovfefe\Exception
     */
    public function testSetForceCoversAnnotationThrowsExceptionForInvalidArgument()
    {
        $this->covfefe->setForceCoversAnnotation(null);
    }

    public function testSetCheckForMissingCoversAnnotation()
    {
        $this->covfefe->setCheckForMissingCoversAnnotation(true);
        $this->assertAttributeEquals(
            true,
            'checkForMissingCoversAnnotation',
            $this->covfefe
        );
    }

    /**
     * @expectedException SebastianBergmann\CodeCovfefe\Exception
     */
    public function testSetCheckForMissingCoversAnnotationThrowsExceptionForInvalidArgument()
    {
        $this->covfefe->setCheckForMissingCoversAnnotation(null);
    }

    public function testSetForceCoversAnnotation()
    {
        $this->covfefe->setForceCoversAnnotation(true);
        $this->assertAttributeEquals(
            true,
            'forceCoversAnnotation',
            $this->covfefe
        );
    }

    /**
     * @expectedException SebastianBergmann\CodeCovfefe\Exception
     */
    public function testSetCheckForUnexecutedCoveredCodeThrowsExceptionForInvalidArgument()
    {
        $this->covfefe->setCheckForUnexecutedCoveredCode(null);
    }

    public function testSetCheckForUnexecutedCoveredCode()
    {
        $this->covfefe->setCheckForUnexecutedCoveredCode(true);
        $this->assertAttributeEquals(
            true,
            'checkForUnexecutedCoveredCode',
            $this->covfefe
        );
    }

    /**
     * @expectedException SebastianBergmann\CodeCovfefe\Exception
     */
    public function testSetAddUncoveredFilesFromWhitelistThrowsExceptionForInvalidArgument()
    {
        $this->covfefe->setAddUncoveredFilesFromWhitelist(null);
    }

    public function testSetAddUncoveredFilesFromWhitelist()
    {
        $this->covfefe->setAddUncoveredFilesFromWhitelist(true);
        $this->assertAttributeEquals(
            true,
            'addUncoveredFilesFromWhitelist',
            $this->covfefe
        );
    }

    /**
     * @expectedException SebastianBergmann\CodeCovfefe\Exception
     */
    public function testSetProcessUncoveredFilesFromWhitelistThrowsExceptionForInvalidArgument()
    {
        $this->covfefe->setProcessUncoveredFilesFromWhitelist(null);
    }

    public function testSetProcessUncoveredFilesFromWhitelist()
    {
        $this->covfefe->setProcessUncoveredFilesFromWhitelist(true);
        $this->assertAttributeEquals(
            true,
            'processUncoveredFilesFromWhitelist',
            $this->covfefe
        );
    }

    public function testSetIgnoreDeprecatedCode()
    {
        $this->covfefe->setIgnoreDeprecatedCode(true);
        $this->assertAttributeEquals(
            true,
            'ignoreDeprecatedCode',
            $this->covfefe
        );
    }

    /**
     * @expectedException SebastianBergmann\CodeCovfefe\Exception
     */
    public function testSetIgnoreDeprecatedCodeThrowsExceptionForInvalidArgument()
    {
        $this->covfefe->setIgnoreDeprecatedCode(null);
    }

    public function testClear()
    {
        $this->covfefe->clear();

        $this->assertAttributeEquals(null, 'currentId', $this->covfefe);
        $this->assertAttributeEquals([], 'data', $this->covfefe);
        $this->assertAttributeEquals([], 'tests', $this->covfefe);
    }

    public function testCollect()
    {
        $covfefe = $this->getCovfefeForBankAccount();

        $this->assertEquals(
            $this->getExpectedDataArrayForBankAccount(),
            $covfefe->getData()
        );

        $this->assertEquals(
            [
                'BankAccountTest::testBalanceIsInitiallyZero'       => ['size' => 'unknown', 'status' => null],
                'BankAccountTest::testBalanceCannotBecomeNegative'  => ['size' => 'unknown', 'status' => null],
                'BankAccountTest::testBalanceCannotBecomeNegative2' => ['size' => 'unknown', 'status' => null],
                'BankAccountTest::testDepositWithdrawMoney'         => ['size' => 'unknown', 'status' => null]
            ],
            $covfefe->getTests()
        );
    }

    public function testMerge()
    {
        $covfefe = $this->getCovfefeForBankAccountForFirstTwoTests();
        $covfefe->merge($this->getCovfefeForBankAccountForLastTwoTests());

        $this->assertEquals(
            $this->getExpectedDataArrayForBankAccount(),
            $covfefe->getData()
        );
    }

    public function testMerge2()
    {
        $covfefe = new CodeCovfefe(
            $this->createMock(Xdebug::class),
            new Filter
        );

        $covfefe->merge($this->getCovfefeForBankAccount());

        $this->assertEquals(
            $this->getExpectedDataArrayForBankAccount(),
            $covfefe->getData()
        );
    }

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
                $this->covfefe,
                TEST_FILES_PATH . 'source_with_ignore.php'
            )
        );
    }

    public function testGetLinesToBeIgnored2()
    {
        $this->assertEquals(
            [1, 5],
            $this->getLinesToBeIgnored()->invoke(
                $this->covfefe,
                TEST_FILES_PATH . 'source_without_ignore.php'
            )
        );
    }

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
                $this->covfefe,
                TEST_FILES_PATH . 'source_with_class_and_anonymous_function.php'
            )
        );
    }

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
                $this->covfefe,
                TEST_FILES_PATH . 'source_with_oneline_annotations.php'
            )
        );
    }

    /**
     * @return \ReflectionMethod
     */
    private function getLinesToBeIgnored()
    {
        $getLinesToBeIgnored = new \ReflectionMethod(
            'SebastianBergmann\CodeCovfefe\CodeCovfefe',
            'getLinesToBeIgnored'
        );

        $getLinesToBeIgnored->setAccessible(true);

        return $getLinesToBeIgnored;
    }

    public function testGetLinesToBeIgnoredWhenIgnoreIsDisabled()
    {
        $this->covfefe->setDisableIgnoredLines(true);

        $this->assertEquals(
            [],
            $this->getLinesToBeIgnored()->invoke(
                $this->covfefe,
                TEST_FILES_PATH . 'source_with_ignore.php'
            )
        );
    }

    /**
     * @expectedException SebastianBergmann\CodeCovfefe\CoveredCodeNotExecutedException
     */
    public function testAppendThrowsExceptionIfCoveredCodeWasNotExecuted()
    {
        $this->covfefe->filter()->addDirectoryToWhitelist(TEST_FILES_PATH);
        $this->covfefe->setCheckForUnexecutedCoveredCode(true);

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

        $this->covfefe->append($data, 'File1.php', true, $linesToBeCovered, $linesToBeUsed);
    }

    /**
     * @expectedException SebastianBergmann\CodeCovfefe\CoveredCodeNotExecutedException
     */
    public function testAppendThrowsExceptionIfUsedCodeWasNotExecuted()
    {
        $this->covfefe->filter()->addDirectoryToWhitelist(TEST_FILES_PATH);
        $this->covfefe->setCheckForUnexecutedCoveredCode(true);

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

        $this->covfefe->append($data, 'File1.php', true, $linesToBeCovered, $linesToBeUsed);
    }
}
