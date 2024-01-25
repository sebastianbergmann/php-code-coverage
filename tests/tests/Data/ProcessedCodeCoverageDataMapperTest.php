<?php declare(strict_types=1);
/*
 * This file is part of phpunit/php-code-coverage.
 *
 * (c) Sebastian Bergmann <sebastian@phpunit.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace SebastianBergmann\CodeCoverage\Data;

use SebastianBergmann\CodeCoverage\Report\Xml\Facade;
use SebastianBergmann\CodeCoverage\TestCase;
use FilesystemIterator;

final class ProcessedCodeCoverageDataMapperTest extends TestCase
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

        foreach (new FilesystemIterator(self::$TEST_TMP_PATH) as $fileInfo) {
            /* @var \SplFileInfo $fileInfo */
            unlink($fileInfo->getPathname());
        }
    }

    public function testToJsonLineCoverageForBankAccount(): void
    {
        $coverage = $this->getLineCoverageForBankAccount()->getData();
        $decodedJson = $this->getDecodedJsonForProcessedCodeCoverage($coverage);

        $this->assertEquals(
            $coverage->lineCoverage(),
            $decodedJson[ProcessedCodeCoverageDataMapper::KEY_LINE_COVERAGE],
        );

        $this->assertEquals(
            $coverage->functionCoverage(),
            $decodedJson[ProcessedCodeCoverageDataMapper::KEY_FUNCTION_COVERAGE],
        );
    }

    public function testToJsonPathCoverageForBankAccount(): void
    {
        $coverage = $this->getPathCoverageForBankAccount()->getData();
        $decodedJson = $this->getDecodedJsonForProcessedCodeCoverage($coverage);

        $this->assertEquals(
            $coverage->lineCoverage(),
            $decodedJson[ProcessedCodeCoverageDataMapper::KEY_LINE_COVERAGE],
        );

        $this->assertEquals(
            $coverage->functionCoverage(),
            $decodedJson[ProcessedCodeCoverageDataMapper::KEY_FUNCTION_COVERAGE],
        );
    }

    public function testFromJsonCoverageForBankAccount(): void
    {
        $coverage = $this->getPathCoverageForBankAccount()->getData();
        $unserializedCoverage = $this->serializeAndUnserializeToJson($coverage);

        $this->assertEquals(
            $coverage->lineCoverage(),
            $unserializedCoverage->lineCoverage(),
        );

        $this->assertEquals(
            $coverage->functionCoverage(),
            $unserializedCoverage->functionCoverage(),
        );
    }

    public function testFromJsonPathCoverageForBankAccount(): void
    {
        $coverage = $this->getPathCoverageForBankAccount()->getData();
        $unserializedCoverage = $this->serializeAndUnserializeToJson($coverage);

        $this->assertEquals(
            $coverage->lineCoverage(),
            $unserializedCoverage->lineCoverage(),
        );

        $this->assertEquals(
            $coverage->functionCoverage(),
            $unserializedCoverage->functionCoverage(),
        );
    }

    /**
    * I don't expect this test to survive in the PR, but I am trying to 
    * produce the final XML format via the JSON serialization to ensure
    * that I have everything I need in the JSON format.
    */
    public function testFromJsonLineCoverageForBankAccountToXml(): void
    {
        $coverage = $this->getLineCoverageForBankAccount();
        $unserializedCoverage = $this->serializeAndUnserializeToJson($coverage->getData());
        $coverage->setData($unserializedCoverage);

        $expectedFilesPath = self::$TEST_REPORT_PATH_SOURCE . DIRECTORY_SEPARATOR . 'CoverageForBankAccount';

        $xml = new Facade('1.0.0');
        $xml->process($coverage, self::$TEST_TMP_PATH);

        $this->assertFilesEquals($expectedFilesPath, self::$TEST_TMP_PATH);
    }

    private function getDecodedJsonForProcessedCodeCoverage(ProcessedCodeCoverageData $processedCodeCoverage): array
    {
        $dataMapper = new ProcessedCodeCoverageDataMapper();
        $json = $dataMapper->toJson($processedCodeCoverage);

        return json_decode($json, true);
    }

    /**
    * Doing it this way while the JSON format is being developed, though I expect we'd have a
    * fixture file in the future
    **/
    private function serializeAndUnserializeToJson(ProcessedCodeCoverageData $processedCodeCoverage): ProcessedCodeCoverageData
    {
        $dataMapper = new ProcessedCodeCoverageDataMapper();
        $json = $dataMapper->toJson($processedCodeCoverage);

        // Instantiate a new data mapper out of an abundance of caution to ensure we have no 
        // persisted state from the serializing instance.
        $dataMapper = new ProcessedCodeCoverageDataMapper();

        return $dataMapper->fromJson($json);
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

        foreach ($expectedFilesIterator as $fileInfo) {
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

