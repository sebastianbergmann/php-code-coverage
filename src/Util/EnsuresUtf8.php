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

use function assert;
use function mb_check_encoding;
use function mb_convert_encoding;
use function mb_detect_encoding;

/**
 * @internal This class is not covered by the backward compatibility promise for phpunit/php-code-coverage
 *
 * @no-named-arguments Parameter names are not covered by the backward compatibility promise for phpunit/php-code-coverage
 */
trait EnsuresUtf8
{
    private function ensureUtf8(string $value): string
    {
        if (mb_check_encoding($value, 'UTF-8')) {
            return $value;
        }

        $encoding = mb_detect_encoding($value, ['Windows-1252', 'ISO-8859-1'], true);

        assert($encoding !== false);

        return mb_convert_encoding($value, 'UTF-8', $encoding);
    }
}
