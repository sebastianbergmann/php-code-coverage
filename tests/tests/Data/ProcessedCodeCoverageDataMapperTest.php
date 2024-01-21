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
    public function testToJson(): void
    {
        $coverage = $this->getLineCoverageForBankAccountForFirstTwoTests()->getData();
        $dataMapper = new ProcessedCodeCoverageDataMapper();
        $json = $dataMapper->toJson($coverage);

        $decodedJson = json_decode($json, true);

        $this->assertEquals(
            $coverage->lineCoverage(),
            $decodedJson['line_coverage'],
        );
    }
}

