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

final class InvalidCodeCoverageTargetException extends RuntimeException implements Exception
{
    /**
     * @param non-empty-string $target
     */
    public function __construct(string $target)
    {
        parent::__construct(
            sprintf(
                '%s is not a valid target for code coverage',
                $target,
            ),
        );
    }
}
