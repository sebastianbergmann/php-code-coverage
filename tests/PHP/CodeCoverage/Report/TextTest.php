<?php
/*
 * This file is part of the PHP_CodeCoverage package.
 *
 * (c) Sebastian Bergmann <sebastian@phpunit.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

require_once dirname(dirname(dirname(dirname(__FILE__)))) . DIRECTORY_SEPARATOR . 'TestCase.php';

/**
 * Tests for the PHP_CodeCoverage_Report_Text class.
 *
 * @since Class available since Release 3.1.0
 */
class PHP_CodeCoverage_Report_TextTest extends PHP_CodeCoverage_TestCase
{
    /**
     * @covers PHP_CodeCoverage_Report_Text
     */
    public function testTextForBankAccountTest()
    {
        $text = new PHP_CodeCoverage_Report_Text(50, 90, false, false);

        $this->assertStringMatchesFormatFile(
            TEST_FILES_PATH . 'BankAccount-text.txt',
            str_replace(PHP_EOL, "\n", $text->process($this->getCoverageForBankAccount()))
        );
    }

    /**
     * @covers PHP_CodeCoverage_Report_Text
     */
    public function testTextForFileWithIgnoredLines()
    {
        $text = new PHP_CodeCoverage_Report_Text(50, 90, false, false);

        $this->assertStringMatchesFormatFile(
            TEST_FILES_PATH . 'ignored-lines-text.txt',
            str_replace(PHP_EOL, "\n", $text->process($this->getCoverageForFileWithIgnoredLines()))
        );
    }

    /**
     * @covers PHP_CodeCoverage_Report_Text
     */
    public function testTextForClassWithAnonymousFunction()
    {
        $text = new PHP_CodeCoverage_Report_Text(50, 90, false, false);

        $this->assertStringMatchesFormatFile(
            TEST_FILES_PATH . 'class-with-anonymous-function-text.txt',
            str_replace(PHP_EOL, "\n", $text->process($this->getCoverageForClassWithAnonymousFunction()))
        );
    }
}
