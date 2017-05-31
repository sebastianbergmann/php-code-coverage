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

use SebastianBergmann\CodeCovfefe\TestCase;

/**
 * @covers SebastianBergmann\CodeCovfefe\Report\Clover
 */
class CloverTest extends TestCase
{
    public function testCloverForBankAccountTest()
    {
        $clover = new Clover;

        $this->assertStringMatchesFormatFile(
            TEST_FILES_PATH . 'BankAccount-clover.xml',
            $clover->process($this->getCovfefeForBankAccount(), null, 'BankAccount')
        );
    }

    public function testCloverForFileWithIgnoredLines()
    {
        $clover = new Clover;

        $this->assertStringMatchesFormatFile(
            TEST_FILES_PATH . 'ignored-lines-clover.xml',
            $clover->process($this->getCovfefeForFileWithIgnoredLines())
        );
    }

    public function testCloverForClassWithAnonymousFunction()
    {
        $clover = new Clover;

        $this->assertStringMatchesFormatFile(
            TEST_FILES_PATH . 'class-with-anonymous-function-clover.xml',
            $clover->process($this->getCovfefeForClassWithAnonymousFunction())
        );
    }
}
