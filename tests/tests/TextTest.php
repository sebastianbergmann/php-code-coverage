<?php declare(strict_types=1);
/*
 * This file is part of phpunit/php-code-coverage.
 *
 * (c) Sebastian Bergmann <sebastian@phpunit.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace SebastianBergmann\CodeCoverage\Report;

use SebastianBergmann\CodeCoverage\TestCase;

/**
 * @covers \SebastianBergmann\CodeCoverage\Report\Text
 */
final class TextTest extends TestCase
{
    public function testTextForBankAccountTest(): void
    {
        $text = new Text(50, 90, false, false);

        $this->assertStringMatchesFormatFile(
            TEST_FILES_PATH . 'BankAccount-text.txt',
            \str_replace(\PHP_EOL, "\n", $text->process($this->getLineCoverageForBankAccount()))
        );
    }

    public function testTextOnlySummaryForBankAccountTest(): void
    {
        $text = new Text(50, 90, false, true);

        $this->assertStringMatchesFormatFile(
            TEST_FILES_PATH . 'BankAccount-text-summary.txt',
            \str_replace(\PHP_EOL, "\n", $text->process($this->getLineCoverageForBankAccount()))
        );
    }

    public function testTextForNamespacedBankAccountTest(): void
    {
        $text = new Text(50, 90, true, false);

        $this->assertStringMatchesFormatFile(
            TEST_FILES_PATH . 'NamespacedBankAccount-text.txt',
            \str_replace(\PHP_EOL, "\n", $text->process($this->getLineCoverageForNamespacedBankAccount()))
        );
    }

    public function testTextForFileWithIgnoredLines(): void
    {
        $text = new Text(50, 90, false, false);

        $this->assertStringMatchesFormatFile(
            TEST_FILES_PATH . 'ignored-lines-text.txt',
            \str_replace(\PHP_EOL, "\n", $text->process($this->getCoverageForFileWithIgnoredLines()))
        );
    }

    public function testTextForClassWithAnonymousFunction(): void
    {
        $text = new Text(50, 90, false, false);

        $this->assertStringMatchesFormatFile(
            TEST_FILES_PATH . 'class-with-anonymous-function-text.txt',
            \str_replace(\PHP_EOL, "\n", $text->process($this->getCoverageForClassWithAnonymousFunction()))
        );
    }
}
