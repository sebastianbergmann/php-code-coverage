<?php
/*
 * This file is part of the php-code-coverage package.
 *
 * (c) Sebastian Bergmann <sebastian@phpunit.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SebastianBergmann\CodeCoverage;

require __DIR__ . '/../_files/BankAccount.php';
require __DIR__ . '/../_files/BankAccountTest.php';

use SebastianBergmann\CodeCoverage\Driver\Driver;
use SebastianBergmann\CodeCoverage\Driver\PHPDBG;
use SebastianBergmann\CodeCoverage\Driver\Xdebug;

/**
 * @covers SebastianBergmann\CodeCoverage\CodeCoverage
 */
class CodeCoverageTest extends TestCase
{
    /**
     * @var CodeCoverage
     */
    private $coverage;

    protected function setUp()
    {
        $this->coverage = new CodeCoverage;
    }

    public function testCanBeConstructedForXdebugWithoutGivenFilterObject()
    {
        if (PHP_SAPI == 'phpdbg') {
            $this->markTestSkipped('Requires PHP CLI and Xdebug');
        }

        $this->assertAttributeInstanceOf(
            Xdebug::class,
            'driver',
            $this->coverage
        );

        $this->assertAttributeInstanceOf(
            Filter::class,
            'filter',
            $this->coverage
        );
    }

    public function testCanBeConstructedForXdebugWithGivenFilterObject()
    {
        if (PHP_SAPI == 'phpdbg') {
            $this->markTestSkipped('Requires PHP CLI and Xdebug');
        }

        $filter   = new Filter;
        $coverage = new CodeCoverage(null, $filter);

        $this->assertAttributeInstanceOf(
            Xdebug::class,
            'driver',
            $coverage
        );

        $this->assertSame($filter, $coverage->filter());
    }

    public function testCanBeConstructedForPhpdbgWithoutGivenFilterObject()
    {
        if (PHP_SAPI != 'phpdbg') {
            $this->markTestSkipped('Requires PHPDBG');
        }

        $this->assertAttributeInstanceOf(
            PHPDBG::class,
            'driver',
            $this->coverage
        );

        $this->assertAttributeInstanceOf(
            Filter::class,
            'filter',
            $this->coverage
        );
    }

    public function testCanBeConstructedForPhpdbgWithGivenFilterObject()
    {
        if (PHP_SAPI != 'phpdbg') {
            $this->markTestSkipped('Requires PHPDBG');
        }

        $filter   = new Filter;
        $coverage = new CodeCoverage(null, $filter);

        $this->assertAttributeInstanceOf(
            PHPDBG::class,
            'driver',
            $coverage
        );

        $this->assertSame($filter, $coverage->filter());
    }

    public function testCannotStopWithInvalidSecondArgument()
    {
        $this->expectException(Exception::class);

        $this->coverage->stop(true, null);
    }

    public function testCannotAppendWithInvalidArgument()
    {
        $this->expectException(Exception::class);

        $this->coverage->append([], null);
    }


    public function testSetCacheTokens()
    {
        $lineFilter = new LineFilter();
        $coverage = new CodeCoverage(null, null, null, $lineFilter);

        $coverage->setCacheTokens(true);
        self::assertSame(true, $coverage->getCacheTokens());
        self::assertSame(true, $lineFilter->getCacheTokens());

        $coverage->setCacheTokens(false);
        self::assertSame(false, $coverage->getCacheTokens());
        self::assertSame(false, $lineFilter->getCacheTokens());
    }

    public function testSetCheckForUnintentionallyCoveredCode()
    {
        $this->coverage->setCheckForUnintentionallyCoveredCode(true);

        $this->assertAttributeEquals(
            true,
            'checkForUnintentionallyCoveredCode',
            $this->coverage
        );
    }

    public function testSetCheckForMissingCoversAnnotation()
    {
        $this->coverage->setCheckForMissingCoversAnnotation(true);

        $this->assertAttributeEquals(
            true,
            'checkForMissingCoversAnnotation',
            $this->coverage
        );
    }

    public function testSetForceCoversAnnotation()
    {
        $this->coverage->setForceCoversAnnotation(true);

        $this->assertAttributeEquals(
            true,
            'forceCoversAnnotation',
            $this->coverage
        );
    }

    public function testSetCheckForUnexecutedCoveredCode()
    {
        $this->coverage->setCheckForUnexecutedCoveredCode(true);

        $this->assertAttributeEquals(
            true,
            'checkForUnexecutedCoveredCode',
            $this->coverage
        );
    }

    public function testSetAddUncoveredFilesFromWhitelist()
    {
        $this->coverage->setAddUncoveredFilesFromWhitelist(true);

        $this->assertAttributeEquals(
            true,
            'addUncoveredFilesFromWhitelist',
            $this->coverage
        );
    }

    public function testSetProcessUncoveredFilesFromWhitelist()
    {
        $this->coverage->setProcessUncoveredFilesFromWhitelist(true);

        $this->assertAttributeEquals(
            true,
            'processUncoveredFilesFromWhitelist',
            $this->coverage
        );
    }

    public function testSetIgnoreDeprecatedCode()
    {
        $lineFilter = new LineFilter();
        $coverage = new CodeCoverage(null, null, null, $lineFilter);

        $coverage->setIgnoreDeprecatedCode(true);
        self::assertSame(true, $lineFilter->getIgnoreDeprecatedCode());

        $coverage->setIgnoreDeprecatedCode(false);
        self::assertSame(false, $lineFilter->getIgnoreDeprecatedCode());
    }

    public function testSetDisableIgnoredLines()
    {
        $lineFilter = new LineFilter();
        $coverage = new CodeCoverage(null, null, null, $lineFilter);

        $coverage->setDisableIgnoredLines(true);
        self::assertSame(true, $lineFilter->getDisableIgnoredLines());

        $coverage->setDisableIgnoredLines(false);
        self::assertSame(false, $lineFilter->getDisableIgnoredLines());
    }

    public function testClear()
    {
        $this->coverage->clear();

        $this->assertAttributeEquals(null, 'currentId', $this->coverage);
        $this->assertAttributeEquals([], 'data', $this->coverage);
        $this->assertAttributeEquals([], 'tests', $this->coverage);
    }

    public function testCollect()
    {
        $coverage = $this->getCoverageForBankAccount();

        $this->assertEquals(
            $this->getExpectedDataArrayForBankAccount(),
            $coverage->getData()
        );

        $this->assertEquals(
            [
                'BankAccountTest::testBalanceIsInitiallyZero'       => ['size' => 'unknown', 'status' => -1],
                'BankAccountTest::testBalanceCannotBecomeNegative'  => ['size' => 'unknown', 'status' => -1],
                'BankAccountTest::testBalanceCannotBecomeNegative2' => ['size' => 'unknown', 'status' => -1],
                'BankAccountTest::testDepositWithdrawMoney'         => ['size' => 'unknown', 'status' => -1]
            ],
            $coverage->getTests()
        );
    }

    public function testMerge()
    {
        $coverage = $this->getCoverageForBankAccountForFirstTwoTests();
        $coverage->merge($this->getCoverageForBankAccountForLastTwoTests());

        $this->assertEquals(
            $this->getExpectedDataArrayForBankAccount(),
            $coverage->getData()
        );
    }

    public function testMergeReverseOrder()
    {
        $coverage = $this->getCoverageForBankAccountForLastTwoTests();
        $coverage->merge($this->getCoverageForBankAccountForFirstTwoTests());

        $this->assertEquals(
            $this->getExpectedDataArrayForBankAccountInReverseOrder(),
            $coverage->getData()
        );
    }

    public function testMerge2()
    {
        $coverage = new CodeCoverage(
            $this->createMock(Driver::class),
            new Filter
        );

        $coverage->merge($this->getCoverageForBankAccount());

        $this->assertEquals(
            $this->getExpectedDataArrayForBankAccount(),
            $coverage->getData()
        );
    }

    public function testAppendThrowsExceptionIfCoveredCodeWasNotExecuted()
    {
        $this->coverage->filter()->addDirectoryToWhitelist(TEST_FILES_PATH);
        $this->coverage->setCheckForUnexecutedCoveredCode(true);

        $data = [
            TEST_FILES_PATH . 'BankAccount.php' => [
                29 => Driver::LINE_NOT_EXECUTED,
                31 => Driver::LINE_NOT_EXECUTED
            ]
        ];

        $linesToBeCovered = [
            TEST_FILES_PATH . 'BankAccount.php' => [
                22,
                24
            ]
        ];

        $linesToBeUsed = [];

        $this->expectException(CoveredCodeNotExecutedException::class);

        $this->coverage->append($data, 'File1.php', true, $linesToBeCovered, $linesToBeUsed);
    }

    public function testAppendThrowsExceptionIfUsedCodeWasNotExecuted()
    {
        $this->coverage->filter()->addDirectoryToWhitelist(TEST_FILES_PATH);
        $this->coverage->setCheckForUnexecutedCoveredCode(true);

        $data = [
            TEST_FILES_PATH . 'BankAccount.php' => [
                29 => Driver::LINE_NOT_EXECUTED,
                31 => Driver::LINE_NOT_EXECUTED
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

        $this->expectException(CoveredCodeNotExecutedException::class);

        $this->coverage->append($data, 'File1.php', true, $linesToBeCovered, $linesToBeUsed);
    }
}
