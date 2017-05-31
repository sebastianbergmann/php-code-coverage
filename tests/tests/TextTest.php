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
 * @covers SebastianBergmann\CodeCovfefe\Report\Text
 */
class TextTest extends TestCase
{
    public function testTextForBankAccountTest()
    {
        $text = new Text(50, 90, false, false);

        $this->assertStringMatchesFormatFile(
            TEST_FILES_PATH . 'BankAccount-text.txt',
            str_replace(PHP_EOL, "\n", $text->process($this->getCovfefeForBankAccount()))
        );
    }

    public function testTextForFileWithIgnoredLines()
    {
        $text = new Text(50, 90, false, false);

        $this->assertStringMatchesFormatFile(
            TEST_FILES_PATH . 'ignored-lines-text.txt',
            str_replace(PHP_EOL, "\n", $text->process($this->getCovfefeForFileWithIgnoredLines()))
        );
    }

    public function testTextForClassWithAnonymousFunction()
    {
        $text = new Text(50, 90, false, false);

        $this->assertStringMatchesFormatFile(
            TEST_FILES_PATH . 'class-with-anonymous-function-text.txt',
            str_replace(PHP_EOL, "\n", $text->process($this->getCovfefeForClassWithAnonymousFunction()))
        );
    }
}
