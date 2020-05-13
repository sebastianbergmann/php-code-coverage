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
final class Xdebug implements Driver
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
    }

    /**
     * Start collection of code coverage information.
     */
    public function start(bool $determineUnusedAndDead = true): void
    {
        if ($determineUnusedAndDead) {
            \xdebug_start_code_coverage(\XDEBUG_CC_UNUSED | \XDEBUG_CC_DEAD_CODE);
        } else {
            \xdebug_start_code_coverage();
        }
    }

    /**
     * Stop collection of code coverage information.
     */
    public function stop(): RawCodeCoverageData
    {
        $data = \xdebug_get_code_coverage();

        \xdebug_stop_code_coverage();

        return new RawCodeCoverageData($data);
    }
}
