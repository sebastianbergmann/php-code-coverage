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

final class ProcessedCodeCoverageDataTest extends TestCase
{
    public function testMergeWithLineCoverage(): void
    {
        $coverage = $this->getLineCoverageForBankAccountForFirstTwoTests()->getData();

        $coverage->merge($this->getLineCoverageForBankAccountForLastTwoTests()->getData());

        $this->assertEquals(
            $this->getExpectedLineCoverageDataArrayForBankAccount(),
            $coverage->getLineCoverage()
        );
    }

    public function testMergeWithPathCoverage(): void
    {
        $coverage = $this->getPathCoverageForBankAccountForFirstTwoTests()->getData();

        $coverage->merge($this->getPathCoverageForBankAccountForLastTwoTests()->getData());

        $this->assertEquals(
            $this->getExpectedPathCoverageDataArrayForBankAccount(),
            $coverage->getFunctionCoverage()
        );
    }

    public function testMergeWithPathCoverageIntoEmpty(): void
    {
        $coverage = new ProcessedCodeCoverageData();

        $coverage->merge($this->getPathCoverageForBankAccount()->getData());

        $this->assertEquals(
            $this->getExpectedPathCoverageDataArrayForBankAccount(),
            $coverage->getFunctionCoverage()
        );
    }

    public function testMergeOfAPreviouslyUnseenLine(): void
    {
        $newCoverage = new ProcessedCodeCoverageData;

        $newCoverage->setLineCoverage(
            [
                '/some/path/SomeClass.php' => [
                    12  => [],
                    34  => null,
                ],
            ]
        );

        $existingCoverage = new ProcessedCodeCoverageData;

        $existingCoverage->merge($newCoverage);

        $this->assertArrayHasKey(12, $existingCoverage->getLineCoverage()['/some/path/SomeClass.php']);
    }
}
