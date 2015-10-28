<?php
/*
 * This file is part of the PHP_CodeCoverage package.
 *
 * (c) Sebastian Bergmann <sebastian@phpunit.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 * @since Class available since Release 3.0.0
 */
class PHP_CodeCoverage_InvalidArgumentException extends InvalidArgumentException implements PHP_CodeCoverage_Exception
{
    /**
     * @param  int                                       $argument
     * @param  string                                    $type
     * @param  mixed                                     $value
     * @return PHP_CodeCoverage_InvalidArgumentException
     */
    public static function create($argument, $type, $value = null)
    {
        $stack = debug_backtrace(0);

        return new self(
            sprintf(
                'Argument #%d%sof %s::%s() must be a %s',
                $argument,
                $value !== null ? ' (' . gettype($value) . '#' . $value . ')' : ' (No Value) ',
                $stack[1]['class'],
                $stack[1]['function'],
                $type
            )
        );
    }
}
