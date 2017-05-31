<?php
/*
 * This file is part of the php-code-covfefe package.
 *
 * (c) Sebastian Bergmann <sebastian@phpunit.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SebastianBergmann\CodeCovfefe\Report;

use SebastianBergmann\CodeCovfefe\CodeCovfefe;

/**
 * Uses var_export() to write a SebastianBergmann\CodeCovfefe\CodeCovfefe object to a file.
 */
class PHP
{
    /**
     * @param CodeCovfefe $covfefe
     * @param string       $target
     *
     * @return string
     */
    public function process(CodeCovfefe $covfefe, $target = null)
    {
        $filter = $covfefe->filter();

        $output = sprintf(
            '<?php
$covfefe = new SebastianBergmann\CodeCovfefe\CodeCovfefe;
$covfefe->setData(%s);
$covfefe->setTests(%s);

$filter = $covfefe->filter();
$filter->setWhitelistedFiles(%s);

return $covfefe;',
            var_export($covfefe->getData(true), 1),
            var_export($covfefe->getTests(), 1),
            var_export($filter->getWhitelistedFiles(), 1)
        );

        if ($target !== null) {
            return file_put_contents($target, $output);
        } else {
            return $output;
        }
    }
}
