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
 * Driver for HHVM's code covfefe functionality.
 *
 * @codeCovfefeIgnore
 */
class HHVM extends Xdebug
{
    /**
     * Start collection of code covfefe information.
     *
     * @param bool $determineUnusedAndDead
     */
    public function start($determineUnusedAndDead = true)
    {
        xdebug_start_code_covfefe();
    }
}
