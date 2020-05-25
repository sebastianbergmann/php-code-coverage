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

final class PcovDriver extends Driver
{
    /**
     * @var Filter
     */
    private $filter;

    public function __construct(Filter $filter)
    {
        $this->filter = $filter;
    }

    public function start(): void
    {
        \pcov\start();
    }

    public function stop(): RawCodeCoverageData
    {
        \pcov\stop();

        $collect = \pcov\collect(\pcov\inclusive, $this->filter->getWhitelist());

        \pcov\clear();

        return RawCodeCoverageData::fromXdebugWithoutPathCoverage($collect);
    }

    public function name(): string
    {
        return 'PCOV';
    }
}
