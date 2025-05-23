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
use PHPUnit\Framework\Attributes\Small;
use SebastianBergmann\CodeCoverage\TestCase;

#[CoversClass(Text::class)]
#[Small]
final class TextTest extends TestCase
{
    public function testLineCoverageForBankAccountTest(): void
    {
        $text = new Text(Thresholds::default(), false, false);

        $this->assertStringMatchesFormatFile(
            TEST_FILES_PATH . 'Report/Text/BankAccount-line.txt',
            str_replace(PHP_EOL, "\n", $text->process($this->getLineCoverageForBankAccount())),
        );
    }

    public function testPathCoverageForBankAccountTest(): void
    {
        $text = new Text(Thresholds::default(), false, false);

        $this->assertStringMatchesFormatFile(
            TEST_FILES_PATH . 'Report/Text/BankAccount-path.txt',
            str_replace(PHP_EOL, "\n", $text->process($this->getPathCoverageForBankAccount())),
        );
    }

    public function testTextOnlySummaryForBankAccountTest(): void
    {
        $text = new Text(Thresholds::default(), false, true);

        $this->assertStringMatchesFormatFile(
            TEST_FILES_PATH . 'Report/Text/BankAccount-summary.txt',
            str_replace(PHP_EOL, "\n", $text->process($this->getLineCoverageForBankAccount())),
        );
    }

    public function testTextForNamespacedBankAccountTest(): void
    {
        $text = new Text(Thresholds::default(), true, false);

        $this->assertStringMatchesFormatFile(
            TEST_FILES_PATH . 'Report/Text/NamespacedBankAccount.txt',
            str_replace(PHP_EOL, "\n", $text->process($this->getLineCoverageForNamespacedBankAccount())),
        );
    }

    public function testTextForNamespacedBankAccountTestWhenColorsAreEnabled(): void
    {
        $text = new Text(Thresholds::default(), true, false);

        $this->assertStringMatchesFormatFile(
            TEST_FILES_PATH . 'Report/Text/NamespacedBankAccount-colors.txt',
            str_replace(PHP_EOL, "\n", $text->process($this->getLineCoverageForNamespacedBankAccount(), true)),
        );
    }

    public function testTextForFileWithIgnoredLines(): void
    {
        $text = new Text(Thresholds::default(), false, false);

        $this->assertStringMatchesFormatFile(
            TEST_FILES_PATH . 'Report/Text/ignored-lines.txt',
            str_replace(PHP_EOL, "\n", $text->process($this->getCoverageForFileWithIgnoredLines())),
        );
    }

    public function testTextForClassWithAnonymousFunction(): void
    {
        $text = new Text(Thresholds::default(), false, false);

        $this->assertStringMatchesFormatFile(
            TEST_FILES_PATH . 'Report/Text/class-with-anonymous-function.txt',
            str_replace(PHP_EOL, "\n", $text->process($this->getCoverageForClassWithAnonymousFunction())),
        );
    }

    public function testUncoveredFilesAreIncludedWhenConfiguredTest(): void
    {
        $text = new Text(Thresholds::default(), false, false);

        $this->assertStringMatchesFormatFile(
            TEST_FILES_PATH . 'Report/Text/BankAccountWithUncovered-line.txt',
            str_replace(PHP_EOL, "\n", $text->process($this->getCoverageForFilesWithUncoveredIncluded())),
        );
    }

    public function testUncoveredFilesAreExcludedWhenConfiguredTest(): void
    {
        $text = new Text(Thresholds::default(), false, false);

        $this->assertStringMatchesFormatFile(
            TEST_FILES_PATH . 'Report/Text/BankAccountWithoutUncovered-line.txt',
            str_replace(PHP_EOL, "\n", $text->process($this->getCoverageForFilesWithUncoveredExcluded())),
        );
    }
}
