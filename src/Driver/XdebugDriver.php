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

use const XDEBUG_CC_BRANCH_CHECK;
use const XDEBUG_CC_DEAD_CODE;
use const XDEBUG_CC_UNUSED;
use const XDEBUG_FILTER_CODE_COVERAGE;
use const XDEBUG_PATH_INCLUDE;
use function extension_loaded;
use function in_array;
use function phpversion;
use function version_compare;
use function xdebug_get_code_coverage;
use function xdebug_info;
use function xdebug_set_filter;
use function xdebug_start_code_coverage;
use function xdebug_stop_code_coverage;
use SebastianBergmann\CodeCoverage\Data\RawCodeCoverageData;
use SebastianBergmann\CodeCoverage\Filter;

/**
 * @internal This class is not covered by the backward compatibility promise for phpunit/php-code-coverage
 *
 * @no-named-arguments Parameter names are not covered by the backward compatibility promise for phpunit/php-code-coverage
 *
 * @see https://xdebug.org/docs/code_coverage#xdebug_get_code_coverage
 *
 * @phpstan-import-type CodeCoverageWithoutPathCoverageType from RawCodeCoverageData as XdebugCodeCoverageWithoutPathCoverageType
 * @phpstan-import-type CodeCoverageWithPathCoverageType from RawCodeCoverageData as XdebugCodeCoverageWithPathCoverageType
 */
final class XdebugDriver extends Driver
{
    /**
     * @throws XdebugNotAvailableException
     * @throws XdebugNotEnabledException
     * @throws XdebugVersionNotSupportedException
     */
    public function __construct(Filter $filter)
    {
        $this->ensureXdebugIsAvailable();

        if (!$filter->isEmpty()) {
            xdebug_set_filter(
                XDEBUG_FILTER_CODE_COVERAGE,
                XDEBUG_PATH_INCLUDE,
                $filter->files(),
            );
        }
    }

    public function start(): void
    {
        $flags = XDEBUG_CC_UNUSED;

        if ($this->granularity() === Granularity::LineBranchAndPath) {
            $flags |= XDEBUG_CC_DEAD_CODE | XDEBUG_CC_BRANCH_CHECK;
        }

        xdebug_start_code_coverage($flags);
    }

    public function stop(): RawCodeCoverageData
    {
        $data = xdebug_get_code_coverage();

        xdebug_stop_code_coverage();

        if ($this->granularity() === Granularity::LineBranchAndPath) {
            $this->ensureWithPathCoverage($data);

            return RawCodeCoverageData::fromXdebugWithPathCoverage($data);
        }

        $this->ensureWithoutPathCoverage($data);

        return RawCodeCoverageData::fromXdebugWithoutPathCoverage($data);
    }

    public function name(): string
    {
        return 'Xdebug';
    }

    public function version(): string
    {
        $version = phpversion('xdebug');

        if ($version === false || $version === '') {
            // @codeCoverageIgnoreStart
            throw new XdebugNotAvailableException;
            // @codeCoverageIgnoreEnd
        }

        return $version;
    }

    protected function canCollectBranchCoverage(): bool
    {
        return true;
    }

    protected function canCollectPathCoverage(): bool
    {
        return true;
    }

    /**
     * The shape of the data returned by xdebug_get_code_coverage() is
     * determined by the flags that were passed to xdebug_start_code_coverage()
     * in start(): when XDEBUG_CC_BRANCH_CHECK was set, path coverage is
     * included.
     *
     * @param array<non-empty-string, mixed> $data
     *
     * @phpstan-assert XdebugCodeCoverageWithPathCoverageType $data
     */
    private function ensureWithPathCoverage(array $data): void
    {
    }

    /**
     * @param array<non-empty-string, mixed> $data
     *
     * @phpstan-assert XdebugCodeCoverageWithoutPathCoverageType $data
     *
     * @see ensureWithPathCoverage()
     */
    private function ensureWithoutPathCoverage(array $data): void
    {
    }

    /**
     * @throws XdebugNotAvailableException
     * @throws XdebugNotEnabledException
     * @throws XdebugVersionNotSupportedException
     */
    private function ensureXdebugIsAvailable(): void
    {
        if (!extension_loaded('xdebug')) {
            throw new XdebugNotAvailableException;
        }

        if (!version_compare($this->version(), '3.1', '>=')) {
            throw new XdebugVersionNotSupportedException($this->version());
        }

        if (!in_array('coverage', xdebug_info('mode'), true)) {
            throw new XdebugNotEnabledException;
        }
    }
}
