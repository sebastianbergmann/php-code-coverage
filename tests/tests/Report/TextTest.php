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

use const PHP_EOL;
use function str_replace;
use PHPUnit\Framework\Attributes\CoversClass;
use SebastianBergmann\CodeCoverage\TestCase;

#[CoversClass(Text::class)]
final class TextTest extends TestCase
{
    public function testLineCoverageForBankAccountTest(): void
    {
        $text = new Text(Thresholds::default(), false, false);

        $this->assertStringMatchesFormatFile(
            TEST_FILES_PATH . 'BankAccount-text-line.txt',
            str_replace(PHP_EOL, "\n", $text->process($this->getLineCoverageForBankAccount())),
        );
    }

    public function testPathCoverageForBankAccountTest(): void
    {
        $text = new Text(Thresholds::default(), false, false);

        $this->assertStringMatchesFormatFile(
            TEST_FILES_PATH . 'BankAccount-text-path.txt',
            str_replace(PHP_EOL, "\n", $text->process($this->getPathCoverageForBankAccount())),
        );
    }

    public function testTextOnlySummaryForBankAccountTest(): void
    {
        $text = new Text(Thresholds::default(), false, true);

        $this->assertStringMatchesFormatFile(
            TEST_FILES_PATH . 'BankAccount-text-summary.txt',
            str_replace(PHP_EOL, "\n", $text->process($this->getLineCoverageForBankAccount())),
        );
    }

    public function testTextForNamespacedBankAccountTest(): void
    {
        $text = new Text(Thresholds::default(), true, false);

        $this->assertStringMatchesFormatFile(
            TEST_FILES_PATH . 'NamespacedBankAccount-text.txt',
            str_replace(PHP_EOL, "\n", $text->process($this->getLineCoverageForNamespacedBankAccount())),
        );
    }

    public function testTextForNamespacedBankAccountTestWhenColorsAreEnabled(): void
    {
        $text = new Text(Thresholds::default(), true, false);

        $this->assertStringMatchesFormatFile(
            TEST_FILES_PATH . 'NamespacedBankAccount-text-with-colors.txt',
            str_replace(PHP_EOL, "\n", $text->process($this->getLineCoverageForNamespacedBankAccount(), true)),
        );
    }

    public function testTextForFileWithIgnoredLines(): void
    {
        $text = new Text(Thresholds::default(), false, false);

        $this->assertStringMatchesFormatFile(
            TEST_FILES_PATH . 'ignored-lines-text.txt',
            str_replace(PHP_EOL, "\n", $text->process($this->getCoverageForFileWithIgnoredLines())),
        );
    }

    public function testTextForClassWithAnonymousFunction(): void
    {
        $text = new Text(Thresholds::default(), false, false);

        $this->assertStringMatchesFormatFile(
            TEST_FILES_PATH . 'class-with-anonymous-function-text.txt',
            str_replace(PHP_EOL, "\n", $text->process($this->getCoverageForClassWithAnonymousFunction())),
        );
    }

    public function testUncoveredFilesAreIncludedWhenConfiguredTest(): void
    {
        $text = new Text(Thresholds::default(), false, false);

        $this->assertStringMatchesFormatFile(
            TEST_FILES_PATH . 'BankAccountWithUncovered-text-line.txt',
            str_replace(PHP_EOL, "\n", $text->process($this->getCoverageForFilesWithUncoveredIncluded())),
        );
    }

    public function testUncoveredFilesAreExcludedWhenConfiguredTest(): void
    {
        $text = new Text(Thresholds::default(), false, false);

        $this->assertStringMatchesFormatFile(
            TEST_FILES_PATH . 'BankAccountWithoutUncovered-text-line.txt',
            str_replace(PHP_EOL, "\n", $text->process($this->getCoverageForFilesWithUncoveredExcluded())),
        );
    }
}
