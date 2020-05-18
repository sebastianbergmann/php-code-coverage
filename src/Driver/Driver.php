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

use SebastianBergmann\CodeCoverage\BranchAndPathCoverageNotSupportedException;
use SebastianBergmann\CodeCoverage\DeadCodeDetectionNotSupportedException;
use SebastianBergmann\CodeCoverage\RawCodeCoverageData;

/**
 * Interface for code coverage drivers.
 */
abstract class Driver
{
    /**
     * @var int
     *
     * @see http://xdebug.org/docs/code_coverage
     */
    public const LINE_EXECUTED = 1;

    /**
     * @var int
     *
     * @see http://xdebug.org/docs/code_coverage
     */
    public const LINE_NOT_EXECUTED = -1;

    /**
     * @var int
     *
     * @see http://xdebug.org/docs/code_coverage
     */
    public const LINE_NOT_EXECUTABLE = -2;

    protected $detectDeadCode = false;

    protected $collectBranchAndPathCoverage = false;

    /**
     * Does this driver support detecting dead code?
     */
    abstract public function canDetectDeadCode(): bool;

    /**
     * Does this driver support collecting branch and path coverage?
     */
    abstract public function canCollectBranchAndPathCoverage(): bool;

    /**
     * Detect dead code
     */
    public function detectDeadCode(bool $flag): void
    {
        if ($flag && !$this->canDetectDeadCode()) {
            throw new DeadCodeDetectionNotSupportedException;
        }

        $this->detectDeadCode = $flag;
    }

    /**
     * Collecting path coverage
     */
    public function collectBranchAndPathCoverage(bool $flag): void
    {
        if ($flag && !$this->canCollectBranchAndPathCoverage()) {
            throw new BranchAndPathCoverageNotSupportedException;
        }

        $this->collectBranchAndPathCoverage = $flag;
    }

    /**
     * Is this driver detecting dead code?
     */
    public function detectingDeadCode(): bool
    {
        return $this->detectDeadCode;
    }

    /**
     * Is this driver collecting branch and path coverage?
     */
    public function collectingBranchAndPathCoverage(): bool
    {
        return $this->collectBranchAndPathCoverage;
    }

    /**
     * Start collection of code coverage information.
     */
    abstract public function start(): void;

    /**
     * Stop collection of code coverage information.
     */
    abstract public function stop(): RawCodeCoverageData;
}
