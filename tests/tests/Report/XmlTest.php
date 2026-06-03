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
use function unlink;
use DateTimeImmutable;
use FilesystemIterator;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Medium;
use SebastianBergmann\CodeCoverage\TestCase;
use SebastianBergmann\Environment\Runtime;

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

        foreach (new FilesystemIterator(TEST_FILES_PATH . 'tmp') as $path => $fileInfo) {
            /* @var \SplFileInfo $fileInfo */
            unlink($fileInfo->getPathname());
        }
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

        $this->assertStringContainsString('<directory name="Target"', file_get_contents(TEST_FILES_PATH . 'tmp' . DIRECTORY_SEPARATOR . 'index.xml'));
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

        return file_get_contents(TEST_FILES_PATH . 'tmp' . DIRECTORY_SEPARATOR . 'index.xml');
    }

    private function concatenatedReportFiles(): string
    {
        $buffer = '';

        foreach (new FilesystemIterator(TEST_FILES_PATH . 'tmp') as $fileInfo) {
            /* @var \SplFileInfo $fileInfo */
            $buffer .= file_get_contents($fileInfo->getPathname());
        }

        return $buffer;
    }

    private function assertFilesEquals(string $expectedFilesPath, string $actualFilesPath): void
    {
        $expectedFilesIterator = new FilesystemIterator($expectedFilesPath);
        $actualFilesIterator   = new FilesystemIterator($actualFilesPath);

        $this->assertEquals(
            iterator_count($expectedFilesIterator),
            iterator_count($actualFilesIterator),
            'Generated files and expected files not match',
        );

        foreach ($expectedFilesIterator as $path => $fileInfo) {
            /* @var \SplFileInfo $fileInfo */
            $filename = $fileInfo->getFilename();

            $actualFile = $actualFilesPath . DIRECTORY_SEPARATOR . $filename;

            $this->assertFileExists($actualFile);

            $this->assertStringMatchesFormatFile(
                $fileInfo->getPathname(),
                file_get_contents($actualFile),
                "{$filename} not match",
            );
        }
    }
}
