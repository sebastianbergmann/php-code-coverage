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

use SebastianBergmann\CodeCoverage\TestCase;

final class ProcessedCodeCoverageDataMapperTest extends TestCase
{
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
}

