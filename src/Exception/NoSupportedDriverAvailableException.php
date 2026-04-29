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

use function sprintf;
use RuntimeException;
use SebastianBergmann\CodeCoverage\Driver\Granularity;

/**
 * @no-named-arguments Parameter names are not covered by the backward compatibility promise for phpunit/php-code-coverage
 */
final class NoSupportedDriverAvailableException extends RuntimeException implements Exception
{
    public function __construct(Granularity $granularity)
    {
        parent::__construct(
            sprintf(
                'No code coverage driver available that supports %s',
                match ($granularity) {
                    Granularity::Line              => 'line coverage',
                    Granularity::LineAndBranch     => 'line and branch coverage',
                    Granularity::LineBranchAndPath => 'line, branch, and path coverage',
                },
            ),
        );
    }
}
