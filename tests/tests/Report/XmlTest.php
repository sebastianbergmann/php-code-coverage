<?php declare(strict_types=1);
/*
 * This file is part of phpunit/php-code-coverage.
 *
 * (c) Sebastian Bergmann <sebastian@phpunit.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace SebastianBergmann\CodeCoverage\Report\Xml;

use const DIRECTORY_SEPARATOR;
use function file_get_contents;
use function iterator_count;
use function rmdir;
use function unlink;
use DateTimeImmutable;
use FilesystemIterator;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Medium;
use SebastianBergmann\CodeCoverage\Node\Builder;
use SebastianBergmann\CodeCoverage\StaticAnalysis\FileAnalyser;
use SebastianBergmann\CodeCoverage\StaticAnalysis\ParsingSourceAnalyser;
use SebastianBergmann\CodeCoverage\TestCase;
use SebastianBergmann\Environment\Runtime;
use SplFileInfo;

#[CoversClass(Facade::class)]
#[CoversClass(BuildInformation::class)]
#[CoversClass(Coverage::class)]
#[CoversClass(Directory::class)]
#[CoversClass(File::class)]
#[CoversClass(Method::class)]
#[CoversClass(Node::class)]
#[CoversClass(Project::class)]
#[CoversClass(Report::class)]
#[CoversClass(Source::class)]
#[CoversClass(Tests::class)]
#[CoversClass(Totals::class)]
#[CoversClass(Unit::class)]
#[Medium]
final class XmlTest extends TestCase
{
    private static string $TEST_REPORT_PATH_SOURCE;

    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();

        self::$TEST_REPORT_PATH_SOURCE = TEST_FILES_PATH . 'Report' . DIRECTORY_SEPARATOR . 'XML';
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        $this->removeDirectoryContents(TEST_FILES_PATH . 'tmp');
    }

    public function testForBankAccountTest(): void
    {
        $codeCoverage = $this->getLineCoverageForBankAccount();

        $expectedFilesPath = self::$TEST_REPORT_PATH_SOURCE . DIRECTORY_SEPARATOR . 'CoverageForBankAccount';

        $xml = new Facade;

        $xml->process(
            TEST_FILES_PATH . 'tmp',
            $codeCoverage->getReport(),
            $codeCoverage->getTests(),
            new Runtime,
            new DateTimeImmutable,
            '13.1.0',
            '14.0.0',
            'Xdebug',
            '3.5.1',
        );

        $this->assertFilesEquals($expectedFilesPath, TEST_FILES_PATH . 'tmp');
    }

    public function testForBankAccountTestWithoutSource(): void
    {
        $codeCoverage = $this->getLineCoverageForBankAccount();

        $expectedFilesPath = self::$TEST_REPORT_PATH_SOURCE . DIRECTORY_SEPARATOR . 'CoverageForBankAccountWithoutSource';

        $xml = new Facade(false);

        $xml->process(
            TEST_FILES_PATH . 'tmp',
            $codeCoverage->getReport(),
            $codeCoverage->getTests(),
            new Runtime,
            new DateTimeImmutable,
            '13.1.0',
            '14.0.0',
            'Xdebug',
            '3.5.1',
        );

        $this->assertFilesEquals($expectedFilesPath, TEST_FILES_PATH . 'tmp');
    }

    public function testForFileWithIgnoredLines(): void
    {
        $codeCoverage = $this->getCoverageForFileWithIgnoredLines();

        $expectedFilesPath = self::$TEST_REPORT_PATH_SOURCE . DIRECTORY_SEPARATOR . 'CoverageForFileWithIgnoredLines';

        $xml = new Facade;

        $xml->process(
            TEST_FILES_PATH . 'tmp',
            $codeCoverage->getReport(),
            $codeCoverage->getTests(),
            new Runtime,
            new DateTimeImmutable,
            '13.1.0',
            '14.0.0',
            'Xdebug',
            '3.5.1',
        );

        $this->assertFilesEquals($expectedFilesPath, TEST_FILES_PATH . 'tmp');
    }

    public function testForClassWithAnonymousFunction(): void
    {
        $codeCoverage = $this->getCoverageForClassWithAnonymousFunction();

        $expectedFilesPath = self::$TEST_REPORT_PATH_SOURCE . DIRECTORY_SEPARATOR . 'CoverageForClassWithAnonymousFunction';

        $xml = new Facade;

        $xml->process(
            TEST_FILES_PATH . 'tmp',
            $codeCoverage->getReport(),
            $codeCoverage->getTests(),
            new Runtime,
            new DateTimeImmutable,
            '13.1.0',
            '14.0.0',
            'Xdebug',
            '3.5.1',
        );

        $this->assertFilesEquals($expectedFilesPath, TEST_FILES_PATH . 'tmp');
    }

    public function testTraitsAreRendered(): void
    {
        $codeCoverage = $this->getLineCoverageForNamespacedBankAccount();

        (new Facade)->process(
            TEST_FILES_PATH . 'tmp',
            $codeCoverage->getReport(),
            $codeCoverage->getTests(),
            new Runtime,
            new DateTimeImmutable,
            '13.1.0',
            '14.0.0',
            'Xdebug',
            '3.5.1',
        );

        $this->assertStringContainsString('<trait', $this->concatenatedReportFiles());
    }

    public function testNestedDirectoriesAreRendered(): void
    {
        (new Facade)->process(
            TEST_FILES_PATH . 'tmp',
            $this->reportForNestedDirectories(),
            [],
            new Runtime,
            new DateTimeImmutable,
            '13.1.0',
            '14.0.0',
            'Xdebug',
            '3.5.1',
        );

        $index = file_get_contents(TEST_FILES_PATH . 'tmp' . DIRECTORY_SEPARATOR . 'index.xml');

        $this->assertNotFalse($index);
        $this->assertStringContainsString('<directory name="Target"', $index);
    }

    public function testCoveringTestThatHasNoTestDataIsOmittedFromLineCoverage(): void
    {
        $codeCoverage = $this->getLineCoverageForBankAccount();

        $tests = $codeCoverage->getTests();

        unset($tests['BankAccountTest::testDepositWithdrawMoney']);

        $report = (new Builder(new FileAnalyser(new ParsingSourceAnalyser, false, false)))->build(
            $codeCoverage->getData(),
            $tests,
        );

        (new Facade)->process(
            TEST_FILES_PATH . 'tmp',
            $report,
            $tests,
            new Runtime,
            new DateTimeImmutable,
            '13.1.0',
            '14.0.0',
            'Xdebug',
            '3.5.1',
        );

        $contents = file_get_contents(TEST_FILES_PATH . 'tmp' . DIRECTORY_SEPARATOR . 'BankAccount.php.xml');

        $this->assertNotFalse($contents);
        $this->assertStringContainsString('BankAccountTest::testBalanceIsInitiallyZero', $contents);
        $this->assertStringNotContainsString('BankAccountTest::testDepositWithdrawMoney', $contents);
    }

    public function testBuildInformationIsOmittedWhenRuntimeIsNotProvided(): void
    {
        $this->assertStringNotContainsString('<build', $this->indexForBankAccountWithBuildInformation(runtime: null));
    }

    public function testBuildInformationIsOmittedWhenBuildDateIsNotProvided(): void
    {
        $this->assertStringNotContainsString('<build', $this->indexForBankAccountWithBuildInformation(buildDate: null));
    }

    public function testBuildInformationIsOmittedWhenPhpUnitVersionIsNotProvided(): void
    {
        $this->assertStringNotContainsString('<build', $this->indexForBankAccountWithBuildInformation(phpUnitVersion: null));
    }

    public function testBuildInformationIsOmittedWhenCoverageVersionIsNotProvided(): void
    {
        $this->assertStringNotContainsString('<build', $this->indexForBankAccountWithBuildInformation(coverageVersion: null));
    }

    public function testDriverExtensionInformationDefaultsToUnknownWhenNotProvided(): void
    {
        $this->assertStringContainsString(
            '<driver name="unknown" version="unknown"',
            $this->indexForBankAccountWithBuildInformation(driverExtensionName: null, driverExtensionVersion: null),
        );
    }

    private function removeDirectoryContents(string $path): void
    {
        foreach (new FilesystemIterator($path) as $fileInfo) {
            if (!$fileInfo instanceof SplFileInfo) {
                continue; // @codeCoverageIgnore
            }

            if ($fileInfo->isDir()) {
                $this->removeDirectoryContents($fileInfo->getPathname());

                rmdir($fileInfo->getPathname());

                continue;
            }

            unlink($fileInfo->getPathname());
        }
    }

    private function indexForBankAccountWithBuildInformation(?Runtime $runtime = new Runtime, ?DateTimeImmutable $buildDate = new DateTimeImmutable, ?string $phpUnitVersion = '13.1.0', ?string $coverageVersion = '14.0.0', ?string $driverExtensionName = 'Xdebug', ?string $driverExtensionVersion = '3.5.1'): string
    {
        $codeCoverage = $this->getLineCoverageForBankAccount();

        (new Facade)->process(
            TEST_FILES_PATH . 'tmp',
            $codeCoverage->getReport(),
            $codeCoverage->getTests(),
            $runtime,
            $buildDate,
            $phpUnitVersion,
            $coverageVersion,
            $driverExtensionName,
            $driverExtensionVersion,
        );

        $index = file_get_contents(TEST_FILES_PATH . 'tmp' . DIRECTORY_SEPARATOR . 'index.xml');

        $this->assertNotFalse($index);

        return $index;
    }

    private function concatenatedReportFiles(): string
    {
        $buffer = '';

        foreach (new FilesystemIterator(TEST_FILES_PATH . 'tmp') as $fileInfo) {
            if (!$fileInfo instanceof SplFileInfo) {
                continue; // @codeCoverageIgnore
            }

            $contents = file_get_contents($fileInfo->getPathname());

            $this->assertNotFalse($contents);

            $buffer .= $contents;
        }

        return $buffer;
    }

    private function assertFilesEquals(string $expectedFilesPath, string $actualFilesPath): void
    {
        $expectedFilesIterator = new FilesystemIterator($expectedFilesPath);
        $actualFilesIterator   = new FilesystemIterator($actualFilesPath);

        $this->assertSame(
            iterator_count($expectedFilesIterator),
            iterator_count($actualFilesIterator),
            'Generated files and expected files not match',
        );

        foreach ($expectedFilesIterator as $fileInfo) {
            if (!$fileInfo instanceof SplFileInfo) {
                continue; // @codeCoverageIgnore
            }

            $filename = $fileInfo->getFilename();

            $actualFile = $actualFilesPath . DIRECTORY_SEPARATOR . $filename;

            $this->assertFileExists($actualFile);

            $actual = file_get_contents($actualFile);

            $this->assertNotFalse($actual);

            $this->assertStringMatchesFormatFile(
                $fileInfo->getPathname(),
                $actual,
                "{$filename} not match",
            );
        }
    }
}
