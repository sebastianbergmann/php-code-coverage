<?php declare(strict_types=1);
/*
 * This file is part of phpunit/php-code-coverage.
 *
 * (c) Sebastian Bergmann <sebastian@phpunit.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace SebastianBergmann\CodeCoverage\Serialization;

use function sprintf;
use RuntimeException;
use SebastianBergmann\CodeCoverage\Exception;

/**
 * @no-named-arguments Parameter names are not covered by the backward compatibility promise for phpunit/php-code-coverage
 */
final class VersionMismatchException extends RuntimeException implements Exception
{
    public function __construct(int $storedFormat, int $supportedFormat)
    {
        parent::__construct(
            sprintf(
                'Coverage data was written using serialization format %d and cannot be read by code that supports serialization format %d',
                $storedFormat,
                $supportedFormat,
            ),
        );
    }
}
