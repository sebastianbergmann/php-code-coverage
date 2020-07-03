<?php declare(strict_types=1);
/*
 * This file is part of phpunit/php-code-coverage.
 *
 * (c) Sebastian Bergmann <sebastian@phpunit.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace SebastianBergmann\CodeCoverage;

use ReflectionMethod;
use SebastianBergmann\CodeCoverage\Driver\Driver;
use SebastianBergmann\Environment\Runtime;

/**
 * @covers \SebastianBergmann\CodeCoverage\CodeCoverage
 */
final class CodeCoverageTest extends TestCase
{
    /**
     * @var CodeCoverage
     */
    private $coverage;

    protected function setUp(): void
    {
        $runtime = new Runtime;

        if (!$runtime->canCollectCodeCoverage()) {
            $this->markTestSkipped('No code coverage driver available');
        }

        $filter = new Filter;

        $this->coverage = new CodeCoverage(
            Driver::forLineCoverage($filter),
            $filter
        );
    }

    public function testCannotStopWithInvalidSecondArgument(): void
    {
        $this->expectException(Exception::class);

        $this->coverage->stop(true, null);
    }

    public function testCannotAppendWithInvalidArgument(): void
    {
        $this->expectException(Exception::class);

        $this->coverage->append(RawCodeCoverageData::fromXdebugWithoutPathCoverage([]), null);
    }

    public function testCollect(): void
    {
        $coverage = $this->getLineCoverageForBankAccount();

        $this->assertEquals(
            $this->getExpectedLineCoverageDataArrayForBankAccount(),
            $coverage->getData()->lineCoverage()
        );

        $this->assertEquals(
            [
                'BankAccountTest::testBalanceIsInitiallyZero'       => ['size' => 'unknown', 'status' => -1],
                'BankAccountTest::testBalanceCannotBecomeNegative'  => ['size' => 'unknown', 'status' => -1],
                'BankAccountTest::testBalanceCannotBecomeNegative2' => ['size' => 'unknown', 'status' => -1],
                'BankAccountTest::testDepositWithdrawMoney'         => ['size' => 'unknown', 'status' => -1],
            ],
            $coverage->getTests()
        );
    }

    public function testWhitelistFiltering(): void
    {
        $this->coverage->filter()->includeFile(TEST_FILES_PATH . 'BankAccount.php');

        $data = RawCodeCoverageData::fromXdebugWithoutPathCoverage([
            TEST_FILES_PATH . 'BankAccount.php' => [
                29 => -1,
                31 => -1,
            ],
            TEST_FILES_PATH . 'CoverageClassTest.php' => [
                29 => -1,
                31 => -1,
            ],
        ]);

        $this->coverage->append($data, 'A test', true);
        $this->assertContains(TEST_FILES_PATH . 'BankAccount.php', $this->coverage->getData()->coveredFiles());
        $this->assertNotContains(TEST_FILES_PATH . 'CoverageClassTest.php', $this->coverage->getData()->coveredFiles());
    }

    public function testMerge(): void
    {
        $coverage = $this->getLineCoverageForBankAccountForFirstTwoTests();
        $coverage->merge($this->getLineCoverageForBankAccountForLastTwoTests());

        $this->assertEquals(
            $this->getExpectedLineCoverageDataArrayForBankAccount(),
            $coverage->getData()->lineCoverage()
        );
    }

    public function testMergeReverseOrder(): void
    {
        $coverage = $this->getLineCoverageForBankAccountForLastTwoTests();
        $coverage->merge($this->getLineCoverageForBankAccountForFirstTwoTests());

        $this->assertEquals(
            $this->getExpectedLineCoverageDataArrayForBankAccountInReverseOrder(),
            $coverage->getData()->lineCoverage()
        );
    }

    public function testMerge2(): void
    {
        $coverage = new CodeCoverage(
            $this->createStub(Driver::class),
            new Filter
        );

        $coverage->merge($this->getLineCoverageForBankAccount());

        $this->assertEquals(
            $this->getExpectedLineCoverageDataArrayForBankAccount(),
            $coverage->getData()->lineCoverage()
        );
    }

    public function testGetLinesToBeIgnored(): void
    {
        $this->assertEquals(
            [
                3,
                4,
                5,
                11,
                12,
                13,
                14,
                15,
                16,
                18,
                23,
                24,
                25,
                30,
                33,
            ],
            $this->getLinesToBeIgnored()->invoke(
                $this->coverage,
                TEST_FILES_PATH . 'source_with_ignore.php'
            )
        );
    }

    public function testGetLinesToBeIgnored2(): void
    {
        $this->assertEquals(
            [],
            $this->getLinesToBeIgnored()->invoke(
                $this->coverage,
                TEST_FILES_PATH . 'source_without_ignore.php'
            )
        );
    }

    public function testGetLinesToBeIgnored3(): void
    {
        $this->assertEquals(
            [
                3,
            ],
            $this->getLinesToBeIgnored()->invoke(
                $this->coverage,
                TEST_FILES_PATH . 'source_with_class_and_anonymous_function.php'
            )
        );
    }

    public function testGetLinesToBeIgnoredOneLineAnnotations(): void
    {
        $this->assertEquals(
            [
                4,
                9,
                29,
                31,
                32,
                33,
            ],
            $this->getLinesToBeIgnored()->invoke(
                $this->coverage,
                TEST_FILES_PATH . 'source_with_oneline_annotations.php'
            )
        );
    }

    public function testGetLinesToBeIgnoredWhenIgnoreIsDisabled(): void
    {
        $this->coverage->disableAnnotationsForIgnoringCode();

        $this->assertEquals(
            [],
            $this->getLinesToBeIgnored()->invoke(
                $this->coverage,
                TEST_FILES_PATH . 'source_with_ignore.php'
            )
        );
    }

    /**
     * @return ReflectionMethod
     */
    private function getLinesToBeIgnored()
    {
        $getLinesToBeIgnored = new ReflectionMethod(
            'SebastianBergmann\CodeCoverage\CodeCoverage',
            'getLinesToBeIgnored'
        );

        $getLinesToBeIgnored->setAccessible(true);

        return $getLinesToBeIgnored;
    }
}
