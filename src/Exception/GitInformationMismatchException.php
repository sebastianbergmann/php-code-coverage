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
final class GitInformationMismatchException extends RuntimeException implements Exception
{
    public function __construct(string $field, string $expected, string $actual)
    {
        parent::__construct(
            sprintf(
                'Git information mismatch: field "%s" is "%s" in the first file but "%s" in another file',
                $field,
                $expected,
                $actual,
            ),
        );
    }
}
