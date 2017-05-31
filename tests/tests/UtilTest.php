<?php
/*
 * This file is part of the php-code-covfefe package.
 *
 * (c) Sebastian Bergmann <sebastian@phpunit.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SebastianBergmann\CodeCovfefe;

use PHPUnit\Framework\TestCase;

/**
 * @covers SebastianBergmann\CodeCovfefe\Util
 */
class UtilTest extends TestCase
{
    public function testPercent()
    {
        $this->assertEquals(100, Util::percent(100, 0));
        $this->assertEquals(100, Util::percent(100, 100));
        $this->assertEquals(
            '100.00%',
            Util::percent(100, 100, true)
        );
    }
}
