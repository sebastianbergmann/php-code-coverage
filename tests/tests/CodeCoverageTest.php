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
        $this->coverage->setCacheTokens(true);

        $this->assertAttributeEquals(true, 'cacheTokens', $this->coverage);
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

    public function testIgnoreCoversAnnotation()
    {
        $this->coverage->setIgnoreCoversAnnotation(true);

        $this->assertAttributeEquals(
            true,
            'ignoreCoversAnnotation',
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
        $this->coverage->setIgnoreDeprecatedCode(true);

        $this->assertAttributeEquals(
            true,
            'ignoreDeprecatedCode',
            $this->coverage
        );
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
            $this->getExpectedDataArrayForBankAccount2(),
            $coverage->getData()
        );

        $this->assertEquals(
            [
                'BankAccountTest::testBalanceIsInitiallyZero'       => ['size' => 'unknown', 'status' => null],
                'BankAccountTest::testBalanceCannotBecomeNegative'  => ['size' => 'unknown', 'status' => null],
                'BankAccountTest::testBalanceCannotBecomeNegative2' => ['size' => 'unknown', 'status' => null],
                'BankAccountTest::testDepositWithdrawMoney'         => ['size' => 'unknown', 'status' => null]
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

    public function testMerge2()
    {
        $coverage = new CodeCoverage(
            $this->createMock(Driver::class),
            new Filter
        );

        $coverage->merge($this->getCoverageForBankAccount());

        $this->assertEquals(
            $this->getExpectedDataArrayForBankAccount2(),
            $coverage->getData()
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
                $this->coverage,
                TEST_FILES_PATH . 'source_with_ignore.php'
            )
        );
    }

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
     * @return \ReflectionMethod
     */
    private function getLinesToBeIgnored()
    {
        $getLinesToBeIgnored = new \ReflectionMethod(
            'SebastianBergmann\CodeCoverage\CodeCoverage',
            'getLinesToBeIgnored'
        );

        $getLinesToBeIgnored->setAccessible(true);

        return $getLinesToBeIgnored;
    }

    public function testGetLinesToBeIgnoredWhenIgnoreIsDisabled()
    {
        $this->coverage->setDisableIgnoredLines(true);

        $this->assertEquals(
            [
                7,
                11,
                12,
                13,
                16,
                17,
                18,
                19,
                20,
                21,
                22,
                23,
                26,
                27,
                32,
                33,
                34,
                35,
                36,
                37
            ],
            $this->getLinesToBeIgnored()->invoke(
                $this->coverage,
                TEST_FILES_PATH . 'source_with_ignore.php'
            )
        );
    }

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

        $this->expectException(CoveredCodeNotExecutedException::class);

        $this->coverage->append($data, 'File1.php', true, $linesToBeCovered, $linesToBeUsed);
    }

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

        $this->expectException(CoveredCodeNotExecutedException::class);

        $this->coverage->append($data, 'File1.php', true, $linesToBeCovered, $linesToBeUsed);
    }
}
