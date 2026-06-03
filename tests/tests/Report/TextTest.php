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
use PHPUnit\Framework\Attributes\Medium;
use SebastianBergmann\CodeCoverage\TestCase;

#[CoversClass(Text::class)]
#[Medium]
final class TextTest extends TestCase
{
    public function testLineCoverageForBankAccountTest(): void
    {
        $text = new Text(Thresholds::default(), false, false);

        $this->assertStringMatchesFormatFile(
            TEST_FILES_PATH . 'Report/Text/BankAccount-line.txt',
            str_replace(PHP_EOL, "\n", $text->process($this->getLineCoverageForBankAccount()->getReport())),
        );
    }

    public function testPathCoverageForBankAccountTest(): void
    {
        $text = new Text(Thresholds::default(), false, false);

        $this->assertStringMatchesFormatFile(
            TEST_FILES_PATH . 'Report/Text/BankAccount-path.txt',
            str_replace(PHP_EOL, "\n", $text->process($this->getPathCoverageForBankAccount()->getReport())),
        );
    }

    public function testTextOnlySummaryForBankAccountTest(): void
    {
        $text = new Text(Thresholds::default(), false, true);

        $this->assertStringMatchesFormatFile(
            TEST_FILES_PATH . 'Report/Text/BankAccount-summary.txt',
            str_replace(PHP_EOL, "\n", $text->process($this->getLineCoverageForBankAccount()->getReport())),
        );
    }

    public function testTextForNamespacedBankAccountTest(): void
    {
        $text = new Text(Thresholds::default(), true, false);

        $this->assertStringMatchesFormatFile(
            TEST_FILES_PATH . 'Report/Text/NamespacedBankAccount.txt',
            str_replace(PHP_EOL, "\n", $text->process($this->getLineCoverageForNamespacedBankAccount()->getReport())),
        );
    }

    public function testTextForNamespacedBankAccountTestWhenColorsAreEnabled(): void
    {
        $text = new Text(Thresholds::default(), true, false);

        $this->assertStringMatchesFormatFile(
            TEST_FILES_PATH . 'Report/Text/NamespacedBankAccount-colors.txt',
            str_replace(PHP_EOL, "\n", $text->process($this->getLineCoverageForNamespacedBankAccount()->getReport(), true)),
        );
    }

    public function testTextForFileWithIgnoredLines(): void
    {
        $text = new Text(Thresholds::default(), false, false);

        $this->assertStringMatchesFormatFile(
            TEST_FILES_PATH . 'Report/Text/ignored-lines.txt',
            str_replace(PHP_EOL, "\n", $text->process($this->getCoverageForFileWithIgnoredLines()->getReport())),
        );
    }

    public function testTextForClassWithAnonymousFunction(): void
    {
        $text = new Text(Thresholds::default(), false, false);

        $this->assertStringMatchesFormatFile(
            TEST_FILES_PATH . 'Report/Text/class-with-anonymous-function.txt',
            str_replace(PHP_EOL, "\n", $text->process($this->getCoverageForClassWithAnonymousFunction()->getReport())),
        );
    }

    public function testUncoveredFilesAreIncludedWhenConfiguredTest(): void
    {
        $text = new Text(Thresholds::default(), false, false);

        $this->assertStringMatchesFormatFile(
            TEST_FILES_PATH . 'Report/Text/BankAccountWithUncovered-line.txt',
            str_replace(PHP_EOL, "\n", $text->process($this->getCoverageForFilesWithUncoveredIncluded()->getReport())),
        );
    }

    public function testUncoveredFilesAreExcludedWhenConfiguredTest(): void
    {
        $text = new Text(Thresholds::default(), false, false);

        $this->assertStringMatchesFormatFile(
            TEST_FILES_PATH . 'Report/Text/BankAccountWithoutUncovered-line.txt',
            str_replace(PHP_EOL, "\n", $text->process($this->getCoverageForFilesWithUncoveredExcluded()->getReport())),
        );
    }

    public function testTextForReportWithFileWithoutBranchCoverageData(): void
    {
        $text = new Text(Thresholds::default(), false, false);

        $output = str_replace(PHP_EOL, "\n", $text->process($this->reportWithFileWithoutBranchCoverageData()));

        $this->assertStringContainsString('1 file was not loaded during test execution and no branch/path data is available for it', $output);
    }

    public function testTextForReportWithNestedDirectories(): void
    {
        $text = new Text(Thresholds::default(), true, false);

        $output = str_replace(PHP_EOL, "\n", $text->process($this->reportForNestedDirectories()));

        $this->assertStringContainsString('BankAccount', $output);
        $this->assertStringContainsString('TargetClass', $output);
    }
}
