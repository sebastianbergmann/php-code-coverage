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
 * Tests for the PHP_CodeCoverage_Report_Crap4j class.
 *
 * @since Class available since Release 3.1.0
 */
class PHP_CodeCoverage_Report_Crap4jTest extends PHP_CodeCoverage_TestCase
{
    /**
     * @covers PHP_CodeCoverage_Report_Crap4j
     */
    public function testForBankAccountTest()
    {
        $crap4j = new PHP_CodeCoverage_Report_Crap4j;

        $this->assertStringMatchesFormatFile(
            TEST_FILES_PATH . 'BankAccount-crap4j.xml',
            $crap4j->process($this->getCoverageForBankAccount(), null, 'BankAccount')
        );
    }

    /**
     * @covers PHP_CodeCoverage_Report_Crap4j
     */
    public function testForFileWithIgnoredLines()
    {
        $crap4j = new PHP_CodeCoverage_Report_Crap4j;

        $this->assertStringMatchesFormatFile(
            TEST_FILES_PATH . 'ignored-lines-crap4j.xml',
            $crap4j->process($this->getCoverageForFileWithIgnoredLines(), null, 'CoverageForFileWithIgnoredLines')
        );
    }

    /**
     * @covers PHP_CodeCoverage_Report_Crap4j
     */
    public function testForClassWithAnonymousFunction()
    {
        $crap4j = new PHP_CodeCoverage_Report_Crap4j;

        $this->assertStringMatchesFormatFile(
            TEST_FILES_PATH . 'class-with-anonymous-function-crap4j.xml',
            $crap4j->process($this->getCoverageForClassWithAnonymousFunction(), null, 'CoverageForClassWithAnonymousFunction')
        );
    }
}
