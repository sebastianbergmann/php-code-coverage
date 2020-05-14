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

class ProcessedCodeCoverageDataTest extends TestCase
{
    public function testMerge(): void
    {
        $coverage = $this->getCoverageForBankAccountForFirstTwoTests()->getData();
        $coverage->merge($this->getCoverageForBankAccountForLastTwoTests()->getData());

        $this->assertEquals(
            $this->getExpectedDataArrayForBankAccount(),
            $coverage->getLineCoverage()
        );
    }

    public function testMergeOfAPreviouslyUnseenLine(): void
    {
        $newCoverage = new ProcessedCodeCoverageData();
        $newCoverage->setLineCoverage(
            [
                '/some/path/SomeClass.php' => [
                    12  => [],
                    34  => null,
                ],
            ]
        );

        $existingCoverage = new ProcessedCodeCoverageData();
        $existingCoverage->merge($newCoverage);
        $this->assertArrayHasKey(12, $existingCoverage->getLineCoverage()['/some/path/SomeClass.php']);
    }
}
