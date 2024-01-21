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
    const KEY_LINE_COVERAGE = 'line_coverage';

    public function toJson(ProcessedCodeCoverageData $processedCodeCoverageData): string
    {
        $arrayMapping = [
            self::KEY_LINE_COVERAGE => $processedCodeCoverageData->lineCoverage(),
        ];

        return json_encode($arrayMapping);
    }

    public function fromJson(string $json): ProcessedCodeCoverageData
    {
        $unserializedData = json_decode($json, true);
        
        $processedCodeCoverageData = new ProcessedCodeCoverageData();

        $processedCodeCoverageData->setLineCoverage($unserializedData[self::KEY_LINE_COVERAGE]);

        return $processedCodeCoverageData;
    }
}

