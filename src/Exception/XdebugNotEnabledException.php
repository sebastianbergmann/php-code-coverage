<?php declare(strict_types=1);
/*
 * This file is part of phpunit/php-code-coverage.
 *
 * (c) Sebastian Bergmann <sebastian@phpunit.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace SebastianBergmann\CodeCoverage\Driver;

use SebastianBergmann\CodeCoverage\Exception;

final class XdebugNotEnabledException extends \RuntimeException implements Exception
{
    public function __construct($mode_error = false)
    {
        if ($mode_error) {
            parent::__construct('xdebug.mode=coverage has to be set in php.ini');
        } else {
            parent::__construct('xdebug.coverage_enable=On has to be set in php.ini');
        }
    }
}
