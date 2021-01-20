<?php declare(strict_types=1);
/*
 * This file is part of phpunit/php-code-coverage.
 *
 * (c) Sebastian Bergmann <sebastian@phpunit.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace SebastianBergmann\CodeCoverage\Report;

use function addcslashes;
use function dirname;
use function file_put_contents;
use function min;
use function serialize;
use function sprintf;
use function strlen;
use function substr;
use SebastianBergmann\CodeCoverage\CodeCoverage;
use SebastianBergmann\CodeCoverage\Directory;
use SebastianBergmann\CodeCoverage\Driver\WriteOperationFailedException;

final class PHP
{
    private const BATCH_SIZE = 1000000;

    public function process(CodeCoverage $coverage, ?string $target = null): string
    {
        $buffer = sprintf(
            '<?php
return \unserialize(\'%s\');',
            $this->serialize($coverage)
        );

        if ($target !== null) {
            Directory::create(dirname($target));

            if (@file_put_contents($target, $buffer) === false) {
                throw new WriteOperationFailedException($target);
            }
        }

        return $buffer;
    }

    private function serialize(CodeCoverage $coverage): string
    {
        $serialized = serialize($coverage);
        $length     = strlen($serialized);
        $result     = '';

        // Aiming to use less memory by escaping string in batches as addcslashes() seems to require
        // 4-5x the size of string parameter being escaped
        for ($i = 0; $i < $length; $i += self::BATCH_SIZE) {
            $batch = substr($serialized, $i, min(self::BATCH_SIZE, $length - $i));
            $result .= addcslashes($batch, "'");
        }

        return $result;
    }
}
