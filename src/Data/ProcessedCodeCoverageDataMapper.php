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

/**
 * @internal This class is not covered by the backward compatibility promise for phpunit/php-code-coverage
 *
 * @psalm-import-type XdebugFunctionCoverageType from \SebastianBergmann\CodeCoverage\Driver\XdebugDriver
 *
 * @psalm-type TestIdType = string
 */
final class ProcessedCodeCoverageDataMapper
{
    const KEY_LINE_COVERAGE = 'lineCoverage';
    const KEY_FUNCTION_COVERAGE = 'functionCoverage';

    public function toJson(ProcessedCodeCoverageData $processedCodeCoverageData): string
    {
        $arrayMapping = [
            self::KEY_LINE_COVERAGE => $processedCodeCoverageData->lineCoverage(),
            self::KEY_FUNCTION_COVERAGE => $processedCodeCoverageData->functionCoverage(),
        ];

        return json_encode($arrayMapping);
    }

    public function fromJson(string $json): ProcessedCodeCoverageData
    {
        /** @var array<array-key, array<array-key, mixed>> */
        $unserializedData = json_decode($json, true);
        
        $processedCodeCoverageData = new ProcessedCodeCoverageData();

        $processedCodeCoverageData->setLineCoverage($unserializedData[self::KEY_LINE_COVERAGE]);
        $processedCodeCoverageData->setFunctionCoverage($unserializedData[self::KEY_FUNCTION_COVERAGE]);

        return $processedCodeCoverageData;
    }
}

