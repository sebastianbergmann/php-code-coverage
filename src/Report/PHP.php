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

use SebastianBergmann\CodeCoverage\CodeCoverage;
use SebastianBergmann\CodeCoverage\Directory;
use SebastianBergmann\CodeCoverage\RuntimeException;

/**
 * @internal This class is not covered by the backward compatibility promise for phpunit/php-code-coverage
 */
final class PHP
{
    /**
     * @throws \SebastianBergmann\CodeCoverage\RuntimeException
     */
    public function process(CodeCoverage $coverage, ?string $target = null): string
    {
        $buffer = \sprintf(
            '<?php
return \unserialize(\'%s\');',
            \serialize($coverage)
        );

        if ($target !== null) {
            Directory::create(\dirname($target));

            if (@\file_put_contents($target, $buffer) === false) {
                throw new RuntimeException(
                    \sprintf(
                        'Could not write to "%s',
                        $target
                    )
                );
            }
        }

        return $buffer;
    }
}
