<?php declare(strict_types=1);
/*
 * This file is part of phpunit/php-code-coverage.
 *
 * (c) Sebastian Bergmann <sebastian@phpunit.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace SebastianBergmann\CodeCoverage\Report;

use PHPUnit\Framework\Attributes\CoversClass;
use SebastianBergmann\CodeCoverage\TestCase;

#[CoversClass(Summary::class)]
final class SummaryTest extends TestCase
{
    public function testLineCoverageForBankAccountTest(): void
    {
        $report  = $this->getLineCoverageForBankAccount()->getReport();
        $summary = new Summary(
            $report->numberOfExecutableLines(),
            $report->numberOfExecutedLines(),
            $report->numberOfExecutableBranches(),
            $report->numberOfExecutedBranches(),
            $report->numberOfExecutablePaths(),
            $report->numberOfExecutedPaths(),
        );

        $this->assertSame(8, $summary->numberOfExecutableLines());
        $this->assertSame(5, $summary->numberOfExecutedLines());
        $this->assertEqualsWithDelta(62.50, $summary->lineCoverageAsPercentage(), 0.01);
        $this->assertFalse($summary->hasBranchAndPathCoverage());
        $this->assertSame(
            'Lines: 62.50% (5/8)',
            $summary->asString(),
        );
    }

    public function testLineCoveragePercentageIsOneHundredWhenNoExecutableLines(): void
    {
        $summary = new Summary(0, 0, 0, 0, 0, 0);

        $this->assertSame(0, $summary->numberOfExecutableLines());
        $this->assertSame(0, $summary->numberOfExecutedLines());
        $this->assertSame(100.0, $summary->lineCoverageAsPercentage());
        $this->assertFalse($summary->hasBranchAndPathCoverage());
        $this->assertSame(
            'Lines: 100.00% (0/0)',
            $summary->asString(),
        );
    }

    public function testPathCoverageForBankAccountTest(): void
    {
        $report  = $this->getPathCoverageForBankAccount()->getReport();
        $summary = new Summary(
            $report->numberOfExecutableLines(),
            $report->numberOfExecutedLines(),
            $report->numberOfExecutableBranches(),
            $report->numberOfExecutedBranches(),
            $report->numberOfExecutablePaths(),
            $report->numberOfExecutedPaths(),
        );

        $this->assertSame(8, $summary->numberOfExecutableLines());
        $this->assertSame(5, $summary->numberOfExecutedLines());
        $this->assertEqualsWithDelta(62.50, $summary->lineCoverageAsPercentage(), 0.01);
        $this->assertTrue($summary->hasBranchAndPathCoverage());
        $this->assertSame(7, $summary->numberOfExecutableBranches());
        $this->assertSame(3, $summary->numberOfExecutedBranches());
        $this->assertEqualsWithDelta(42.86, $summary->branchCoverageAsPercentage(), 0.01);
        $this->assertSame(5, $summary->numberOfExecutablePaths());
        $this->assertSame(3, $summary->numberOfExecutedPaths());
        $this->assertEqualsWithDelta(60.00, $summary->pathCoverageAsPercentage(), 0.01);
        $this->assertSame(
            'Lines: 62.50% (5/8), Branches: 42.86% (3/7), Paths: 60.00% (3/5)',
            $summary->asString(),
        );
    }
}
