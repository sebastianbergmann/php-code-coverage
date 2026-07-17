<?php declare(strict_types=1);
/*
 * This file is part of phpunit/php-code-coverage.
 *
 * (c) Sebastian Bergmann <sebastian@phpunit.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace SebastianBergmann\CodeCoverage\Driver;

use function sprintf;
use SebastianBergmann\CodeCoverage\BranchCoverageNotSupportedException;
use SebastianBergmann\CodeCoverage\Data\RawCodeCoverageData;
use SebastianBergmann\CodeCoverage\PathCoverageNotSupportedException;

/**
 * @internal This class is not covered by the backward compatibility promise for phpunit/php-code-coverage
 *
 * @no-named-arguments Parameter names are not covered by the backward compatibility promise for phpunit/php-code-coverage
 */
abstract class Driver
{
    /**
     * @see http://xdebug.org/docs/code_coverage
     */
    public const int LINE_NOT_EXECUTABLE = -2;

    /**
     * @see http://xdebug.org/docs/code_coverage
     */
    public const int LINE_NOT_EXECUTED = -1;

    /**
     * Minimum value for an executed line. Values greater than or equal to this
     * are hit counts: drivers that count executions report how often the line
     * was executed, drivers that only know whether a line was executed report
     * LINE_EXECUTED.
     *
     * @see http://xdebug.org/docs/code_coverage
     */
    public const int LINE_EXECUTED = 1;

    /**
     * @see http://xdebug.org/docs/code_coverage
     */
    public const int BRANCH_NOT_HIT = 0;

    /**
     * Minimum value for a traversed branch or path. Values greater than or
     * equal to this are traversal counts: drivers that count report how often
     * the branch or path was traversed, drivers that only know whether it was
     * traversed at all report BRANCH_HIT.
     *
     * @see http://xdebug.org/docs/code_coverage
     */
    public const int BRANCH_HIT      = 1;
    private Granularity $granularity = Granularity::Line;

    public function granularity(): Granularity
    {
        return $this->granularity;
    }

    /**
     * @throws BranchCoverageNotSupportedException
     * @throws PathCoverageNotSupportedException
     */
    public function setGranularity(Granularity $granularity): void
    {
        if (($granularity === Granularity::LineAndBranch || $granularity === Granularity::LineBranchAndPath) &&
            !$this->canCollectBranchCoverage()) {
            throw new BranchCoverageNotSupportedException(
                sprintf('%s does not support branch coverage', $this->nameAndVersion()),
            );
        }

        if ($granularity === Granularity::LineBranchAndPath && !$this->canCollectPathCoverage()) {
            throw new PathCoverageNotSupportedException(
                sprintf('%s does not support path coverage', $this->nameAndVersion()),
            );
        }

        $this->granularity = $granularity;
    }

    /**
     * @return non-empty-string
     */
    abstract public function name(): string;

    /**
     * @return non-empty-string
     */
    abstract public function version(): string;

    public function nameAndVersion(): string
    {
        return $this->name() . ' ' . $this->version();
    }

    abstract public function start(): void;

    abstract public function stop(): RawCodeCoverageData;

    /**
     * Whether this driver reports how often a line was executed (values >= 1
     * in the line coverage data are exact hit counts) or only whether a line
     * was executed at all (values >= 1 mean "executed at least once").
     */
    public function collectsHitCounts(): bool
    {
        return false;
    }

    protected function canCollectBranchCoverage(): bool
    {
        return false;
    }

    protected function canCollectPathCoverage(): bool
    {
        return false;
    }
}
