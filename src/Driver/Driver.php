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

use function phpversion;
use function sprintf;
use function version_compare;
use SebastianBergmann\CodeCoverage\BranchAndPathCoverageNotSupportedException;
use SebastianBergmann\CodeCoverage\DeadCodeDetectionNotSupportedException;
use SebastianBergmann\CodeCoverage\Filter;
use SebastianBergmann\CodeCoverage\NoCodeCoverageDriverAvailableException;
use SebastianBergmann\CodeCoverage\NoCodeCoverageDriverWithPathCoverageSupportAvailableException;
use SebastianBergmann\CodeCoverage\RawCodeCoverageData;
use SebastianBergmann\Environment\Runtime;

/**
 * @internal This class is not covered by the backward compatibility promise for phpunit/php-code-coverage
 */
abstract class Driver
{
    /**
     * @var int
     *
     * @see http://xdebug.org/docs/code_coverage
     */
    public const LINE_NOT_EXECUTABLE = -2;

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
    public const LINE_EXECUTED = 1;

    /**
     * @var int
     *
     * @see http://xdebug.org/docs/code_coverage
     */
    public const BRANCH_NOT_HIT = 0;

    /**
     * @var int
     *
     * @see http://xdebug.org/docs/code_coverage
     */
    public const BRANCH_HIT = 1;

    /**
     * @var bool
     */
    private $collectBranchAndPathCoverage = false;

    /**
     * @var bool
     */
    private $detectDeadCode = false;

    /**
     * @throws NoCodeCoverageDriverAvailableException
     * @throws PcovNotAvailableException
     * @throws PhpdbgNotAvailableException
     * @throws XdebugNotAvailableException
     * @throws Xdebug2NotEnabledException
     * @throws Xdebug3NotEnabledException
     */
    public static function forLineCoverage(Filter $filter): self
    {
        $runtime = new Runtime;

        if ($runtime->hasPHPDBGCodeCoverage()) {
            return new PhpdbgDriver;
        }

        if ($runtime->hasPCOV()) {
            return new PcovDriver($filter);
        }

        if ($runtime->hasXdebug()) {
            if (version_compare(phpversion('xdebug'), '3', '>=')) {
                $driver = new Xdebug3Driver($filter);
            } else {
                $driver = new Xdebug2Driver($filter);
            }

            $driver->enableDeadCodeDetection();

            return $driver;
        }

        throw new NoCodeCoverageDriverAvailableException;
    }

    /**
     * @throws NoCodeCoverageDriverWithPathCoverageSupportAvailableException
     * @throws XdebugNotAvailableException
     * @throws Xdebug2NotEnabledException
     * @throws Xdebug3NotEnabledException
     */
    public static function forLineAndPathCoverage(Filter $filter): self
    {
        $runtime = new Runtime;

        if ($runtime->hasXdebug()) {
            if (version_compare(phpversion('xdebug'), '3', '>=')) {
                $driver = new Xdebug3Driver($filter);
            } else {
                $driver = new Xdebug2Driver($filter);
            }

            $driver->enableDeadCodeDetection();
            $driver->enableBranchAndPathCoverage();

            return $driver;
        }

        throw new NoCodeCoverageDriverWithPathCoverageSupportAvailableException;
    }

    public function canCollectBranchAndPathCoverage(): bool
    {
        return false;
    }

    public function collectsBranchAndPathCoverage(): bool
    {
        return $this->collectBranchAndPathCoverage;
    }

    /**
     * @throws BranchAndPathCoverageNotSupportedException
     */
    public function enableBranchAndPathCoverage(): void
    {
        if (!$this->canCollectBranchAndPathCoverage()) {
            throw new BranchAndPathCoverageNotSupportedException(
                sprintf(
                    '%s does not support branch and path coverage',
                    $this->nameAndVersion()
                )
            );
        }

        $this->collectBranchAndPathCoverage = true;
    }

    public function disableBranchAndPathCoverage(): void
    {
        $this->collectBranchAndPathCoverage = false;
    }

    public function canDetectDeadCode(): bool
    {
        return false;
    }

    public function detectsDeadCode(): bool
    {
        return $this->detectDeadCode;
    }

    /**
     * @throws DeadCodeDetectionNotSupportedException
     */
    public function enableDeadCodeDetection(): void
    {
        if (!$this->canDetectDeadCode()) {
            throw new DeadCodeDetectionNotSupportedException(
                sprintf(
                    '%s does not support dead code detection',
                    $this->nameAndVersion()
                )
            );
        }

        $this->detectDeadCode = true;
    }

    public function disableDeadCodeDetection(): void
    {
        $this->detectDeadCode = false;
    }

    abstract public function nameAndVersion(): string;

    abstract public function start(): void;

    abstract public function stop(): RawCodeCoverageData;
}
