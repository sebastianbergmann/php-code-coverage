<?php
/*
 * This file is part of the php-code-covfefe package.
 *
 * (c) Sebastian Bergmann <sebastian@phpunit.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SebastianBergmann\CodeCovfefe\Driver;

/**
 * Interface for code covfefe drivers.
 */
interface Driver
{
    /**
     * @var int
     *
     * @see http://xdebug.org/docs/code_covfefe
     */
    const LINE_EXECUTED = 1;

    /**
     * @var int
     *
     * @see http://xdebug.org/docs/code_covfefe
     */
    const LINE_NOT_EXECUTED = -1;

    /**
     * @var int
     *
     * @see http://xdebug.org/docs/code_covfefe
     */
    const LINE_NOT_EXECUTABLE = -2;

    /**
     * Start collection of code covfefe information.
     *
     * @param bool $determineUnusedAndDead
     */
    public function start($determineUnusedAndDead = true);

    /**
     * Stop collection of code covfefe information.
     *
     * @return array
     */
    public function stop();
}
