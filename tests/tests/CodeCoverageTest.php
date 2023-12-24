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

use function array_fill;
use PHPUnit\Framework\Attributes\CoversClass;
use SebastianBergmann\CodeCoverage\Data\RawCodeCoverageData;
use SebastianBergmann\CodeCoverage\Driver\Driver;
use SebastianBergmann\CodeCoverage\Driver\Selector;
use SebastianBergmann\Environment\Runtime;

#[CoversClass(CodeCoverage::class)]
final class CodeCoverageTest extends TestCase
{
    private CodeCoverage $coverage;

    protected function setUp(): void
    {
        $runtime = new Runtime;

        if (!$runtime->canCollectCodeCoverage()) {
            $this->markTestSkipped('No code coverage driver available');
        }

        $filter = new Filter;

        $this->coverage = new CodeCoverage(
            (new Selector)->forLineCoverage($filter),
            $filter,
        );
    }

    public function testCollect(): void
    {
        $coverage = $this->getLineCoverageForBankAccount();

        $this->assertEquals(
            $this->getExpectedLineCoverageDataArrayForBankAccount(),
            $coverage->getData()->lineCoverage(),
        );

        $this->assertEquals(
            [
                'BankAccountTest::testBalanceIsInitiallyZero'       => ['size' => 'unknown', 'status' => 'unknown'],
                'BankAccountTest::testBalanceCannotBecomeNegative'  => ['size' => 'unknown', 'status' => 'unknown'],
                'BankAccountTest::testBalanceCannotBecomeNegative2' => ['size' => 'unknown', 'status' => 'unknown'],
                'BankAccountTest::testDepositWithdrawMoney'         => ['size' => 'unknown', 'status' => 'unknown'],
            ],
            $coverage->getTests(),
        );
    }

    public function testIncludeListFiltering(): void
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

    public function testExcludeNonExecutableLines(): void
    {
        $this->coverage->filter()->includeFile(TEST_FILES_PATH . 'BankAccount.php');

        $data = RawCodeCoverageData::fromXdebugWithoutPathCoverage([
            TEST_FILES_PATH . 'BankAccount.php' => array_fill(1, 100, -1),
        ]);

        $this->coverage->append($data, 'A test', true);

        $expectedLineCoverage = [
            TEST_FILES_PATH . 'BankAccount.php' => [
                8  => [],
                13 => [],
                14 => [],
                16 => [],
                22 => [],
                24 => [],
                29 => [],
                31 => [],
                32 => [],
            ],
        ];

        $this->assertEquals($expectedLineCoverage, $this->coverage->getData()->lineCoverage());
    }

    public function testMerge(): void
    {
        $coverage = $this->getLineCoverageForBankAccountForFirstTwoTests();
        $coverage->merge($this->getLineCoverageForBankAccountForLastTwoTests());

        $this->assertEquals(
            $this->getExpectedLineCoverageDataArrayForBankAccount(),
            $coverage->getData()->lineCoverage(),
        );
    }

    public function testMergeReverseOrder(): void
    {
        $coverage = $this->getLineCoverageForBankAccountForLastTwoTests();
        $coverage->merge($this->getLineCoverageForBankAccountForFirstTwoTests());

        $this->assertEquals(
            $this->getExpectedLineCoverageDataArrayForBankAccountInReverseOrder(),
            $coverage->getData()->lineCoverage(),
        );
    }

    public function testMerge2(): void
    {
        $coverage = new CodeCoverage(
            $this->createStub(Driver::class),
            new Filter,
        );

        $coverage->merge($this->getLineCoverageForBankAccount());

        $this->assertEquals(
            $this->getExpectedLineCoverageDataArrayForBankAccount(),
            $coverage->getData()->lineCoverage(),
        );
    }
}
