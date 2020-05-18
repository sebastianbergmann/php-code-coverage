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

use SebastianBergmann\CodeCoverage\Filter;
use SebastianBergmann\CodeCoverage\RawCodeCoverageData;
use SebastianBergmann\CodeCoverage\RuntimeException;

/**
 * Driver for Xdebug's code coverage functionality.
 */
final class Xdebug extends Driver
{
    /**
     * @throws RuntimeException
     */
    public function __construct(Filter $filter)
    {
        if (!\extension_loaded('xdebug')) {
            throw new RuntimeException('This driver requires Xdebug');
        }

        if (!\ini_get('xdebug.coverage_enable')) {
            throw new RuntimeException('xdebug.coverage_enable=On has to be set in php.ini');
        }

        \xdebug_set_filter(\XDEBUG_FILTER_CODE_COVERAGE, \XDEBUG_PATH_WHITELIST, $filter->getWhitelist());
        $this->detectDeadCode = true;
    }

    /**
     * Does this driver support detecting dead code?
     */
    public function canDetectDeadCode(): bool
    {
        return true;
    }

    /**
     * Does this driver support collecting path coverage?
     */
    public function canCollectBranchAndPathCoverage(): bool
    {
        return true;
    }

    /**
     * Start collection of code coverage information.
     */
    public function start(): void
    {
        $flags = \XDEBUG_CC_UNUSED;

        if ($this->detectDeadCode || $this->collectBranchAndPathCoverage) { // branch/path collection requires enabling dead code checks
            $flags |= \XDEBUG_CC_DEAD_CODE;
        }

        if ($this->collectBranchAndPathCoverage) {
            $flags |= \XDEBUG_CC_BRANCH_CHECK;
        }

        \xdebug_start_code_coverage($flags);
    }

    /**
     * Stop collection of code coverage information.
     */
    public function stop(): RawCodeCoverageData
    {
        $data = \xdebug_get_code_coverage();

        \xdebug_stop_code_coverage();

        if ($this->collectBranchAndPathCoverage) {
            return RawCodeCoverageData::fromXdebugWithPathCoverage($data);
        }

        return RawCodeCoverageData::fromXdebugWithoutPathCoverage($data);
    }
}
