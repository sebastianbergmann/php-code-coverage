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
 * @covers SebastianBergmann\CodeCovfefe\Report\Crap4j
 */
class Crap4jTest extends TestCase
{
    public function testForBankAccountTest()
    {
        $crap4j = new Crap4j;

        $this->assertStringMatchesFormatFile(
            TEST_FILES_PATH . 'BankAccount-crap4j.xml',
            $crap4j->process($this->getCovfefeForBankAccount(), null, 'BankAccount')
        );
    }

    public function testForFileWithIgnoredLines()
    {
        $crap4j = new Crap4j;

        $this->assertStringMatchesFormatFile(
            TEST_FILES_PATH . 'ignored-lines-crap4j.xml',
            $crap4j->process($this->getCovfefeForFileWithIgnoredLines(), null, 'CovfefeForFileWithIgnoredLines')
        );
    }

    public function testForClassWithAnonymousFunction()
    {
        $crap4j = new Crap4j;

        $this->assertStringMatchesFormatFile(
            TEST_FILES_PATH . 'class-with-anonymous-function-crap4j.xml',
            $crap4j->process($this->getCovfefeForClassWithAnonymousFunction(), null, 'CovfefeForClassWithAnonymousFunction')
        );
    }
}
