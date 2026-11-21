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
use function substr_count;
use function unlink;
use DateTimeImmutable;
use DOMDocument;
use DOMXPath;
use FilesystemIterator;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Medium;
use SebastianBergmann\CodeCoverage\CodeCoverage;
use SebastianBergmann\CodeCoverage\Node\Builder;
use SebastianBergmann\CodeCoverage\Node\Directory as DirectoryNode;
use SebastianBergmann\CodeCoverage\Node\File as FileNode;
use SebastianBergmann\CodeCoverage\StaticAnalysis\FileAnalyser;
use SebastianBergmann\CodeCoverage\StaticAnalysis\LinesOfCode;
use SebastianBergmann\CodeCoverage\StaticAnalysis\ParsingSourceAnalyser;
use SebastianBergmann\CodeCoverage\TestCase;
use SebastianBergmann\Environment\Runtime;
use SplFileInfo;

/**
 * @phpstan-import-type TestType from CodeCoverage
 */
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

    public function testForBankAccountTestWithBranchAndPathCoverage(): void
    {
        $codeCoverage = $this->getPathCoverageForBankAccount();

        $expectedFilesPath = self::$TEST_REPORT_PATH_SOURCE . DIRECTORY_SEPARATOR . 'PathCoverageForBankAccount';

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

    public function testBranchAndPathCoverageIsOmittedWhenItWasNotCollected(): void
    {
        $codeCoverage = $this->getLineCoverageForBankAccount();

        $this->process($codeCoverage->getReport(), $codeCoverage->getTests());

        $index = $this->xpathFor('index.xml');
        $file  = $this->xpathFor('BankAccount.php.xml');

        $this->assertSame('false', $index->evaluate('string(/c:phpunit/c:build/@branchCoverage)'));
        $this->assertSame(0.0, $index->evaluate('count(//c:branches | //c:paths)'));
        $this->assertSame(0.0, $file->evaluate('count(//c:branches | //c:paths)'));
        $this->assertSame(0.0, $file->evaluate('count(//@executableBranches | //@executedBranches | //@executablePaths | //@executedPaths)'));
    }

    public function testDirectoryReportsNumberOfFilesForWhichBranchAndPathCoverageIsNotAvailable(): void
    {
        $this->process($this->reportWithFileWithoutBranchCoverageData(), []);

        $index = $this->xpathFor('index.xml');
        $root  = '/c:phpunit/c:project/c:directory/c:totals';

        $this->assertSame('true', $index->evaluate('string(/c:phpunit/c:build/@branchCoverage)'));
        $this->assertSame('7', $index->evaluate('string(' . $root . '/c:branches/@count)'));
        $this->assertSame('1', $index->evaluate('string(' . $root . '/c:branches/@filesWithoutData)'));
        $this->assertSame('5', $index->evaluate('string(' . $root . '/c:paths/@count)'));
        $this->assertSame('1', $index->evaluate('string(' . $root . '/c:paths/@filesWithoutData)'));
    }

    public function testBranchAndPathCoverageIsOmittedForFileAndDirectoryForWhichItIsNotAvailable(): void
    {
        $this->process($this->reportWithFileWithoutBranchCoverageData(), []);

        $index     = $this->xpathFor('index.xml');
        $directory = '/c:phpunit/c:project/c:directory/c:directory[@name="Target"]';

        $this->assertSame(1.0, $index->evaluate('count(' . $directory . '/c:totals/c:lines)'));
        $this->assertSame(0.0, $index->evaluate('count(' . $directory . '/c:totals/c:branches | ' . $directory . '/c:totals/c:paths)'));
        $this->assertSame(1.0, $index->evaluate('count(' . $directory . '/c:file/c:totals/c:lines)'));
        $this->assertSame(0.0, $index->evaluate('count(' . $directory . '/c:file/c:totals/c:branches | ' . $directory . '/c:file/c:totals/c:paths)'));

        $file = $this->xpathFor('Target/TargetClass.php.xml');

        $this->assertSame(0.0, $file->evaluate('count(//c:branches | //c:paths)'));
        $this->assertSame(0.0, $file->evaluate('count(//@executableBranches | //@executedBranches | //@executablePaths | //@executedPaths)'));
    }

    public function testFileReportsBranchAndPathCoverageWithoutNumberOfFiles(): void
    {
        $this->process($this->reportWithFileWithoutBranchCoverageData(), []);

        $index = $this->xpathFor('index.xml');
        $file  = '/c:phpunit/c:project/c:directory/c:file[@name="BankAccount.php"]/c:totals';

        $this->assertSame('7', $index->evaluate('string(' . $file . '/c:branches/@count)'));
        $this->assertSame('3', $index->evaluate('string(' . $file . '/c:branches/@tested)'));
        $this->assertSame('5', $index->evaluate('string(' . $file . '/c:paths/@count)'));
        $this->assertSame('3', $index->evaluate('string(' . $file . '/c:paths/@tested)'));
        $this->assertSame(0.0, $index->evaluate('count(' . $file . '/c:branches/@filesWithoutData | ' . $file . '/c:paths/@filesWithoutData)'));
    }

    public function testBranchAndPathCoverageIsOmittedForMethodForWhichItIsNotAvailable(): void
    {
        $codeCoverage = $this->pathCoverageForBankAccountWithPartialBranchAndPathCoverage();

        $this->process($codeCoverage->getReport(), $codeCoverage->getTests());

        $file = $this->xpathFor('BankAccount.php.xml');

        $this->assertSame('4', $file->evaluate('string(//c:method[@name="setBalance"]/@executableBranches)'));
        $this->assertSame('3', $file->evaluate('string(//c:method[@name="setBalance"]/@executedBranches)'));
        $this->assertSame('2', $file->evaluate('string(//c:method[@name="setBalance"]/@executablePaths)'));
        $this->assertSame('1', $file->evaluate('string(//c:method[@name="setBalance"]/@executedPaths)'));
        $this->assertSame(1.0, $file->evaluate('count(//c:method[@name="getBalance"])'));
        $this->assertSame(0.0, $file->evaluate('count(//c:method[@name="getBalance"]/@executableBranches | //c:method[@name="getBalance"]/@executedBranches | //c:method[@name="getBalance"]/@executablePaths | //c:method[@name="getBalance"]/@executedPaths)'));
        $this->assertSame('4', $file->evaluate('string(//c:class[@name="BankAccount"]/@executableBranches)'));
        $this->assertSame('3', $file->evaluate('string(//c:class[@name="BankAccount"]/@executedBranches)'));
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

    public function testCoveringTestThatHasNoTestDataIsStillAttributedInLineCoverage(): void
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
        $this->assertStringContainsString('BankAccountTest::testDepositWithdrawMoney', $contents);
    }

    public function testTestForWhichNoTestDataIsAvailableIsNotAttributedInLineCoverage(): void
    {
        $tests = [
            0 => ['name' => 'ExampleTest::testOne', 'size' => 'small', 'status' => 'passed', 'time' => 0.0],
        ];

        $report = new DirectoryNode('/path/to/src');

        $report->addFile(
            new FileNode(
                'Example.php',
                $report,
                'abc123',
                [1 => [0 => 1, 1 => 1], 2 => [1 => 1]],
                [],
                $tests,
                [],
                [],
                [],
                new LinesOfCode(2, 0, 2),
            ),
        );

        (new Facade(false))->process(
            TEST_FILES_PATH . 'tmp',
            $report,
            ['ExampleTest::testOne' => ['size' => 'small', 'status' => 'passed', 'time' => 0.0]],
        );

        $contents = file_get_contents(TEST_FILES_PATH . 'tmp' . DIRECTORY_SEPARATOR . 'Example.php.xml');

        $this->assertNotFalse($contents);
        $this->assertSame(1, substr_count($contents, '<covered '));
        $this->assertStringContainsString('<covered by="ExampleTest::testOne"', $contents);
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

    /**
     * @param array<non-empty-string, TestType> $tests
     */
    private function process(DirectoryNode $report, array $tests): void
    {
        (new Facade(false))->process(
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
    }

    private function xpathFor(string $file): DOMXPath
    {
        $document = new DOMDocument;

        $this->assertTrue($document->load(TEST_FILES_PATH . 'tmp' . DIRECTORY_SEPARATOR . $file));

        $xpath = new DOMXPath($document);

        $xpath->registerNamespace('c', Facade::XML_NAMESPACE);

        return $xpath;
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
