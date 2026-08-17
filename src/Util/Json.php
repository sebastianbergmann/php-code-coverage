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

use function json_encode;
use function json_last_error_msg;
use function sprintf;
use SebastianBergmann\CodeCoverage\JsonException;

/**
 * @internal This class is not covered by the backward compatibility promise for phpunit/php-code-coverage
 *
 * @no-named-arguments Parameter names are not covered by the backward compatibility promise for phpunit/php-code-coverage
 */
final readonly class Json
{
    /**
     * JSON_THROW_ON_ERROR is deliberately not used here: it would make
     * \JsonException, which is not part of this package's exception
     * hierarchy, escape from every report writer that encodes JSON.
     *
     * @param array<mixed> $data
     *
     * @throws JsonException
     *
     * @return non-empty-string
     */
    public static function encode(array $data, int $flags = 0): string
    {
        $buffer = json_encode($data, $flags);

        if ($buffer === false) {
            throw new JsonException(
                sprintf(
                    'Unable to generate the JSON: %s',
                    json_last_error_msg(),
                ),
            );
        }

        return $buffer;
    }
}
