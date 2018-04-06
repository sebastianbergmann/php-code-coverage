<?php
/*
 * This file is part of the php-code-coverage package.
 *
 * (c) Sebastian Bergmann <sebastian@phpunit.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SebastianBergmann\CodeCoverage\Report;

use SebastianBergmann\CodeCoverage\CodeCoverage;
use SebastianBergmann\CodeCoverage\RuntimeException;

/**
 * Uses var_export() to write a SebastianBergmann\CodeCoverage\CodeCoverage object to a file.
 */
final class PHP
{
    /**
     * @param CodeCoverage $coverage
     * @param null|string  $target
     *
     * @throws \SebastianBergmann\CodeCoverage\RuntimeException
     *
     * @return string
     */
    public function process(CodeCoverage $coverage, ?string $target = null): string
    {
        $filter = $coverage->filter();

        $buffer = \sprintf(
            '<?php
$coverage = new SebastianBergmann\CodeCoverage\CodeCoverage;
$coverage->setData(%s);
$coverage->setTests(%s);

$filter = $coverage->filter();
$filter->setWhitelistedFiles(%s);

return $coverage;',
            \var_export($coverage->getData(true), 1),
            \var_export($coverage->getTests(), 1),
            \var_export($filter->getWhitelistedFiles(), 1)
        );

        if ($target !== null) {
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
