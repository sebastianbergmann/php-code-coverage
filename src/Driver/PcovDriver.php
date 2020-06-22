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

use function extension_loaded;
use function phpversion;
use SebastianBergmann\CodeCoverage\Filter;
use SebastianBergmann\CodeCoverage\RawCodeCoverageData;

/**
 * @internal This class is not covered by the backward compatibility promise for phpunit/php-code-coverage
 */
final class PcovDriver extends Driver
{
    /**
     * @var Filter
     */
    private $filter;

    /**
     * @throws PcovNotAvailableException
     */
    public function __construct(Filter $filter)
    {
        if (!extension_loaded('pcov')) {
            throw new PcovNotAvailableException;
        }

        $this->filter = $filter;
    }

    public function start(): void
    {
        \pcov\start();
    }

    public function stop(): RawCodeCoverageData
    {
        \pcov\stop();

        $collect = \pcov\collect(
            \pcov\inclusive,
            !$this->filter->isEmpty() ? $this->filter->files() : \pcov\waiting()
        );

        \pcov\clear();

        return RawCodeCoverageData::fromXdebugWithoutPathCoverage($collect);
    }

    public function nameAndVersion(): string
    {
        return 'PCOV ' . phpversion('pcov');
    }
}
