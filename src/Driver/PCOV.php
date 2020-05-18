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

/**
 * Driver for PCOV code coverage functionality.
 */
final class PCOV extends Driver
{
    /**
     * @var Filter
     */
    private $filter;

    public function __construct(Filter $filter)
    {
        $this->filter = $filter;
    }

    /**
     * Does this driver support detecting dead code?
     */
    public function canDetectDeadCode(): bool
    {
        return false;
    }

    /**
     * Does this driver support collecting path coverage?
     */
    public function canCollectBranchAndPathCoverage(): bool
    {
        return false;
    }

    /**
     * Start collection of code coverage information.
     */
    public function start(): void
    {
        \pcov\start();
    }

    /**
     * Stop collection of code coverage information.
     */
    public function stop(): RawCodeCoverageData
    {
        \pcov\stop();

        $collect = \pcov\collect(\pcov\inclusive, $this->filter->getWhitelist());

        \pcov\clear();

        return RawCodeCoverageData::fromXdebugWithoutPathCoverage($collect);
    }
}
