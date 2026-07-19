<?php declare(strict_types=1);
/*
 * This file is part of phpunit/php-code-coverage.
 *
 * (c) Sebastian Bergmann <sebastian@phpunit.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace SebastianBergmann\CodeCoverage\Util;

use function str_starts_with;

/**
 * @internal This class is not covered by the backward compatibility promise for phpunit/php-code-coverage
 *
 * @no-named-arguments Parameter names are not covered by the backward compatibility promise for phpunit/php-code-coverage
 */
final class Phar
{
    /**
     * Returns whether this copy of phpunit/php-code-coverage runs from a
     * PHP Archive in which it is bundled with prefixed namespaces, for
     * instance PHPUnit's PHAR distribution.
     */
    public static function isBundled(): bool
    {
        return str_starts_with(self::class, 'PHPUnitPHAR\\');
    }
}
