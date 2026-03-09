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
use function sys_get_temp_dir;
use function tempnam;
use function unlink;
use PHPUnit\Framework\Attributes\CoversClass;
use SebastianBergmann\CodeCoverage\Data\ProcessedCodeCoverageData;
use SebastianBergmann\CodeCoverage\Data\RawCodeCoverageData;
use SebastianBergmann\CodeCoverage\Driver\Driver;
use SebastianBergmann\CodeCoverage\Driver\Selector;
use SebastianBergmann\Environment\Runtime;

#[CoversClass(CodeCoverage::class)]
final class CodeCoverageTest extends TestCase
{
    private ?CodeCoverage $coverage = null;

    protected function setUp(): void
    {
        $runtime = new Runtime;

        if ($runtime->canCollectCodeCoverage()) {
            $filter = new Filter;

            $this->coverage = new CodeCoverage(
                (new Selector)->forLineCoverage($filter),
                $filter,
            );
        }
    }

    public function testCollect(): void
    {
        $this->requireDriver();

        $coverage = $this->getLineCoverageForBankAccount();

        $this->assertEquals(
            $this->getExpectedLineCoverageDataArrayForBankAccount(),
            $coverage->getData()->lineCoverage(),
        );

        $this->assertEquals(
            [
                'BankAccountTest::testBalanceIsInitiallyZero'       => ['size' => 'unknown', 'status' => 'unknown', 'time' => 0.1],
                'BankAccountTest::testBalanceCannotBecomeNegative'  => ['size' => 'unknown', 'status' => 'unknown', 'time' => 0.2],
                'BankAccountTest::testBalanceCannotBecomeNegative2' => ['size' => 'unknown', 'status' => 'unknown', 'time' => 0.3],
                'BankAccountTest::testDepositWithdrawMoney'         => ['size' => 'unknown', 'status' => 'unknown', 'time' => 0.4],
            ],
            $coverage->getTests(),
        );
    }

    public function testIncludeListFiltering(): void
    {
        $coverage = $this->requireDriver();

        $coverage->filter()->includeFile(TEST_FILES_PATH . 'BankAccount.php');

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

        $coverage->append($data, 'A test', true);

        $this->assertContains(TEST_FILES_PATH . 'BankAccount.php', $coverage->getData()->coveredFiles());
        $this->assertNotContains(TEST_FILES_PATH . 'CoverageClassTest.php', $coverage->getData()->coveredFiles());
    }

    public function testExcludeNonExecutableLines(): void
    {
        $coverage = $this->requireDriver();

        $coverage->filter()->includeFile(TEST_FILES_PATH . 'BankAccount.php');

        $data = RawCodeCoverageData::fromXdebugWithoutPathCoverage([
            TEST_FILES_PATH . 'BankAccount.php' => array_fill(1, 100, -1),
        ]);

        $coverage->append($data, 'A test', true);

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

        $this->assertEquals($expectedLineCoverage, $coverage->getData()->lineCoverage());
    }

    public function testMerge(): void
    {
        $this->requireDriver();

        $coverage = $this->getLineCoverageForBankAccountForFirstTwoTests();

        $coverage->merge($this->getLineCoverageForBankAccountForLastTwoTests());

        $this->assertEquals(
            $this->getExpectedLineCoverageDataArrayForBankAccount(),
            $coverage->getData()->lineCoverage(),
        );
    }

    public function testMergeReverseOrder(): void
    {
        $this->requireDriver();

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

    public function testClearCache(): void
    {
        $coverage = $this->requireDriver();

        $coverage->filter()->includeFile(TEST_FILES_PATH . 'BankAccount.php');

        $data = RawCodeCoverageData::fromXdebugWithoutPathCoverage([
            TEST_FILES_PATH . 'BankAccount.php' => [29 => -1],
        ]);

        $coverage->append($data, 'A test', true);

        $report1 = $coverage->getReport();

        $coverage->clearCache();

        $report2 = $coverage->getReport();

        $this->assertNotSame($report1, $report2);
    }

    public function testSetData(): void
    {
        $coverage = new CodeCoverage(
            $this->createStub(Driver::class),
            new Filter,
        );

        $data = new ProcessedCodeCoverageData;

        $coverage->setData($data);

        $this->assertSame($data, $coverage->getData(true));
    }

    public function testSetTests(): void
    {
        $coverage = new CodeCoverage(
            $this->createStub(Driver::class),
            new Filter,
        );

        $tests = [
            'test1' => ['size' => 'small', 'status' => 'success', 'time' => 0.1],
        ];

        $coverage->setTests($tests);

        $this->assertSame($tests, $coverage->getTests());
    }

    public function testEnableAndDisableCheckForUnintentionallyCoveredCode(): void
    {
        $coverage = $this->requireDriver();

        $coverage->enableCheckForUnintentionallyCoveredCode();
        $coverage->disableCheckForUnintentionallyCoveredCode();

        $coverage->filter()->includeFile(TEST_FILES_PATH . 'BankAccount.php');

        $data = RawCodeCoverageData::fromXdebugWithoutPathCoverage([
            TEST_FILES_PATH . 'BankAccount.php' => [29 => 1, 31 => 1],
        ]);

        $coverage->append($data, 'A test', true);

        $this->assertNotEmpty($coverage->getTests());
    }

    public function testIncludeAndExcludeUncoveredFiles(): void
    {
        $coverage = new CodeCoverage(
            $this->createStub(Driver::class),
            new Filter,
        );

        $coverage->excludeUncoveredFiles();
        $coverage->includeUncoveredFiles();

        $this->assertNotNull($coverage->getData(true));
    }

    public function testEnableAndDisableAnnotationsForIgnoringCode(): void
    {
        $coverage = $this->requireDriver();

        $coverage->disableAnnotationsForIgnoringCode();
        $coverage->enableAnnotationsForIgnoringCode();

        $coverage->filter()->includeFile(TEST_FILES_PATH . 'BankAccount.php');

        $data = RawCodeCoverageData::fromXdebugWithoutPathCoverage([
            TEST_FILES_PATH . 'BankAccount.php' => [29 => -1],
        ]);

        $coverage->append($data, 'A test', true);

        $this->assertNotEmpty($coverage->getData()->coveredFiles());
    }

    public function testIgnoreAndDoNotIgnoreDeprecatedCode(): void
    {
        $coverage = new CodeCoverage(
            $this->createStub(Driver::class),
            new Filter,
        );

        $coverage->ignoreDeprecatedCode();
        $coverage->doNotIgnoreDeprecatedCode();

        $this->assertNotNull($coverage->getData(true));
    }

    public function testCacheStaticAnalysisAndDoNotCacheStaticAnalysis(): void
    {
        $coverage = new CodeCoverage(
            $this->createStub(Driver::class),
            new Filter,
        );

        $this->assertFalse($coverage->cachesStaticAnalysis());

        $coverage->cacheStaticAnalysis(sys_get_temp_dir());

        $this->assertTrue($coverage->cachesStaticAnalysis());

        $coverage->doNotCacheStaticAnalysis();

        $this->assertFalse($coverage->cachesStaticAnalysis());
    }

    public function testCacheDirectoryReturnsCacheDirectory(): void
    {
        $coverage = new CodeCoverage(
            $this->createStub(Driver::class),
            new Filter,
        );

        $dir = sys_get_temp_dir();

        $coverage->cacheStaticAnalysis($dir);

        $this->assertSame($dir, $coverage->cacheDirectory());
    }

    public function testCacheDirectoryThrowsWhenNotConfigured(): void
    {
        $coverage = new CodeCoverage(
            $this->createStub(Driver::class),
            new Filter,
        );

        $this->expectException(StaticAnalysisCacheNotConfiguredException::class);

        $coverage->cacheDirectory();
    }

    public function testDisableBranchAndPathCoverageAndCollectsBranchAndPathCoverage(): void
    {
        $driver = $this->createStub(Driver::class);

        $coverage = new CodeCoverage($driver, new Filter);

        $this->assertFalse($coverage->collectsBranchAndPathCoverage());

        $coverage->disableBranchAndPathCoverage();

        $this->assertFalse($coverage->collectsBranchAndPathCoverage());
    }

    public function testAppendThrowsWhenIdIsNull(): void
    {
        $coverage = new CodeCoverage(
            $this->createStub(Driver::class),
            new Filter,
        );

        $this->expectException(TestIdMissingException::class);

        $coverage->append(
            RawCodeCoverageData::fromXdebugWithoutPathCoverage([]),
        );
    }

    public function testAppendReturnsEarlyWhenAppendIsFalse(): void
    {
        $coverage = $this->requireDriver();

        $coverage->filter()->includeFile(TEST_FILES_PATH . 'BankAccount.php');

        $data = RawCodeCoverageData::fromXdebugWithoutPathCoverage([
            TEST_FILES_PATH . 'BankAccount.php' => [29 => 1],
        ]);

        $coverage->append($data, 'A test', false);

        $this->assertSame([], $coverage->getTests());
    }

    public function testAppendReturnsEarlyWhenLineCoverageIsEmpty(): void
    {
        $coverage = $this->requireDriver();

        $data = RawCodeCoverageData::fromXdebugWithoutPathCoverage([]);

        $coverage->append($data, 'A test', true);

        $this->assertSame([], $coverage->getTests());
    }

    public function testGetDataIncludesUncoveredFiles(): void
    {
        $coverage = $this->requireDriver();

        $coverage->filter()->includeFile(TEST_FILES_PATH . 'BankAccount.php');

        $data = $coverage->getData();

        $this->assertContains(TEST_FILES_PATH . 'BankAccount.php', $data->coveredFiles());
    }

    public function testGetDataExcludesUncoveredFilesWhenRaw(): void
    {
        $coverage = new CodeCoverage(
            $this->createStub(Driver::class),
            new Filter,
        );

        $coverage->filter()->includeFile(TEST_FILES_PATH . 'BankAccount.php');

        $data = $coverage->getData(true);

        $this->assertSame([], $data->coveredFiles());
    }

    public function testAnalyserUsesCacheWhenConfigured(): void
    {
        $coverage = $this->requireDriver();

        $tmpDir = tempnam(sys_get_temp_dir(), 'phpcc_');
        unlink($tmpDir);

        $coverage->cacheStaticAnalysis($tmpDir);
        $coverage->filter()->includeFile(TEST_FILES_PATH . 'BankAccount.php');

        $data = RawCodeCoverageData::fromXdebugWithoutPathCoverage([
            TEST_FILES_PATH . 'BankAccount.php' => [29 => -1],
        ]);

        $coverage->append($data, 'A test', true);

        $this->assertTrue($coverage->cachesStaticAnalysis());
        $this->assertSame($tmpDir, $coverage->cacheDirectory());
    }

    public function testDisableAnnotationsForIgnoringCodeSkipsIgnoredLinesFilter(): void
    {
        $coverage = $this->requireDriver();

        $coverage->disableAnnotationsForIgnoringCode();
        $coverage->filter()->includeFile(TEST_FILES_PATH . 'BankAccount.php');

        $data = RawCodeCoverageData::fromXdebugWithoutPathCoverage([
            TEST_FILES_PATH . 'BankAccount.php' => [29 => -1],
        ]);

        $coverage->append($data, 'A test', true);

        $this->assertContains(TEST_FILES_PATH . 'BankAccount.php', $coverage->getData()->coveredFiles());
    }

    private function requireDriver(): CodeCoverage
    {
        if ($this->coverage === null) {
            $this->markTestSkipped('No code coverage driver available');
        }

        return $this->coverage;
    }
}
