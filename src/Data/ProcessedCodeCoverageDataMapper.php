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
    public function toJson(ProcessedCodeCoverageData $processedCodeCoverageData): string
    {
        $arrayMapping = [
            'line_coverage' => $processedCodeCoverageData->lineCoverage(),
        ];

        return json_encode($arrayMapping);
    }
}

