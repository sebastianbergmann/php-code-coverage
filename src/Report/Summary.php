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

use function sprintf;

/**
 * @no-named-arguments Parameter names are not covered by the backward compatibility promise for phpunit/php-code-coverage
 */
final readonly class Summary
{
    /**
     * @var non-negative-int
     */
    private int $numberOfExecutableLines;

    /**
     * @var non-negative-int
     */
    private int $numberOfExecutedLines;

    /**
     * @var non-negative-int
     */
    private int $numberOfExecutableBranches;

    /**
     * @var non-negative-int
     */
    private int $numberOfExecutedBranches;

    /**
     * @var non-negative-int
     */
    private int $numberOfExecutablePaths;

    /**
     * @var non-negative-int
     */
    private int $numberOfExecutedPaths;

    /**
     * @var non-negative-int
     */
    private int $numberOfFilesWithoutBranchCoverageData;

    /**
     * @param non-negative-int $numberOfExecutableLines
     * @param non-negative-int $numberOfExecutedLines
     * @param non-negative-int $numberOfExecutableBranches
     * @param non-negative-int $numberOfExecutedBranches
     * @param non-negative-int $numberOfExecutablePaths
     * @param non-negative-int $numberOfExecutedPaths
     * @param non-negative-int $numberOfFilesWithoutBranchCoverageData
     */
    public function __construct(int $numberOfExecutableLines, int $numberOfExecutedLines, int $numberOfExecutableBranches, int $numberOfExecutedBranches, int $numberOfExecutablePaths, int $numberOfExecutedPaths, int $numberOfFilesWithoutBranchCoverageData = 0)
    {
        $this->numberOfExecutableLines                = $numberOfExecutableLines;
        $this->numberOfExecutedLines                  = $numberOfExecutedLines;
        $this->numberOfExecutableBranches             = $numberOfExecutableBranches;
        $this->numberOfExecutedBranches               = $numberOfExecutedBranches;
        $this->numberOfExecutablePaths                = $numberOfExecutablePaths;
        $this->numberOfExecutedPaths                  = $numberOfExecutedPaths;
        $this->numberOfFilesWithoutBranchCoverageData = $numberOfFilesWithoutBranchCoverageData;
    }

    /**
     * @return non-negative-int
     */
    public function numberOfExecutableLines(): int
    {
        return $this->numberOfExecutableLines;
    }

    /**
     * @return non-negative-int
     */
    public function numberOfExecutedLines(): int
    {
        return $this->numberOfExecutedLines;
    }

    public function lineCoverageAsPercentage(): float
    {
        return $this->percentage($this->numberOfExecutedLines, $this->numberOfExecutableLines);
    }

    public function hasBranchAndPathCoverage(): bool
    {
        return $this->numberOfExecutableBranches > 0;
    }

    /**
     * @return non-negative-int
     */
    public function numberOfExecutableBranches(): int
    {
        return $this->numberOfExecutableBranches;
    }

    /**
     * @return non-negative-int
     */
    public function numberOfExecutedBranches(): int
    {
        return $this->numberOfExecutedBranches;
    }

    public function branchCoverageAsPercentage(): float
    {
        return $this->percentage($this->numberOfExecutedBranches, $this->numberOfExecutableBranches);
    }

    /**
     * @return non-negative-int
     */
    public function numberOfExecutablePaths(): int
    {
        return $this->numberOfExecutablePaths;
    }

    /**
     * @return non-negative-int
     */
    public function numberOfExecutedPaths(): int
    {
        return $this->numberOfExecutedPaths;
    }

    public function pathCoverageAsPercentage(): float
    {
        return $this->percentage($this->numberOfExecutedPaths, $this->numberOfExecutablePaths);
    }

    /**
     * @return non-negative-int
     */
    public function numberOfFilesWithoutBranchCoverageData(): int
    {
        return $this->numberOfFilesWithoutBranchCoverageData;
    }

    /**
     * @return non-empty-string
     */
    public function asString(): string
    {
        $buffer = sprintf(
            'Lines: %01.2F%% (%d/%d)',
            $this->lineCoverageAsPercentage(),
            $this->numberOfExecutedLines,
            $this->numberOfExecutableLines,
        );

        if ($this->hasBranchAndPathCoverage()) {
            $buffer .= sprintf(
                ', Branches: %01.2F%% (%d/%d)',
                $this->branchCoverageAsPercentage(),
                $this->numberOfExecutedBranches,
                $this->numberOfExecutableBranches,
            );

            $buffer .= sprintf(
                ', Paths: %01.2F%% (%d/%d)',
                $this->pathCoverageAsPercentage(),
                $this->numberOfExecutedPaths,
                $this->numberOfExecutablePaths,
            );
        }

        return $buffer;
    }

    /**
     * @param non-negative-int $fraction
     * @param non-negative-int $total
     */
    private function percentage(int $fraction, int $total): float
    {
        if ($total > 0) {
            return ($fraction / $total) * 100;
        }

        return 100.0;
    }
}
