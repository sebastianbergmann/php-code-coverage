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

use SebastianBergmann\CodeCoverage\Data\RawCodeCoverageData;
use SebastianBergmann\CodeCoverage\MethodNotImplementedException;

/**
 * @internal This class is not covered by the backward compatibility promise for phpunit/php-code-coverage
 *
 * @codeCoverageIgnore
 */
final class NullDriver extends Driver
{
    /**
     * @throws MethodNotImplementedException
     */
    public function nameAndVersion(): string
    {
        throw new MethodNotImplementedException;
    }

    /**
     * @throws MethodNotImplementedException
     */
    public function start(): void
    {
        throw new MethodNotImplementedException;
    }

    /**
     * @throws MethodNotImplementedException
     */
    public function stop(): RawCodeCoverageData
    {
        throw new MethodNotImplementedException;
    }
}
