<?php declare(strict_types=1);
/*
 * This file is part of phpunit/php-code-coverage.
 *
 * (c) Sebastian Bergmann <sebastian@phpunit.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace SebastianBergmann\CodeCoverage;

/**
 * Exception that is raised when a driver supplies coverage data in a format that cannot be handled.
 */
final class UnknownCoverageDataFormatException extends RuntimeException
{
    public static function create(string $filename): self
    {
        return new self(
            \sprintf(
                'Coverage data for file "%s" must be in Xdebug-compatible format, see https://xdebug.org/docs/code_coverage',
                $filename
            )
        );
    }
}
