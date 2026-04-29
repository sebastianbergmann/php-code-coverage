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
use SebastianBergmann\CodeCoverage\NoSupportedDriverAvailableException;
use SebastianBergmann\Environment\Runtime;

/**
 * @no-named-arguments Parameter names are not covered by the backward compatibility promise for phpunit/php-code-coverage
 */
final class Selector
{
    /**
     * @throws NoSupportedDriverAvailableException
     * @throws XdebugNotAvailableException
     * @throws XdebugNotEnabledException
     * @throws XdebugVersionNotSupportedException
     */
    public function select(Filter $filter, Granularity $granularity = Granularity::Line): Driver
    {
        $runtime = new Runtime;

        if ($granularity === Granularity::Line && $runtime->hasPCOV()) {
            return new PcovDriver($filter);
        }

        if ($runtime->hasXdebug()) {
            $driver = new XdebugDriver($filter);
            $driver->setGranularity($granularity);

            return $driver;
        }

        throw new NoSupportedDriverAvailableException($granularity);
    }

    /**
     * @throws NoSupportedDriverAvailableException
     * @throws XdebugNotAvailableException
     * @throws XdebugNotEnabledException
     * @throws XdebugVersionNotSupportedException
     *
     * @deprecated
     */
    public function forLineCoverage(Filter $filter): Driver
    {
        return $this->select($filter, Granularity::Line);
    }

    /**
     * @throws NoSupportedDriverAvailableException
     * @throws XdebugNotAvailableException
     * @throws XdebugNotEnabledException
     * @throws XdebugVersionNotSupportedException
     *
     * @deprecated
     */
    public function forLineAndPathCoverage(Filter $filter): Driver
    {
        return $this->select($filter, Granularity::LineBranchAndPath);
    }
}
