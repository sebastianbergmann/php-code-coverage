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

use DOMDocument;
use SebastianBergmann\CodeCoverage\TestCase;

final class CoberturaTest extends TestCase
{
    public function testLineCoverageForBankAccountTest(): void
    {
        $report = (new Cobertura)->process($this->getLineCoverageForBankAccount(), null);

        $this->assertStringMatchesFormatFile(
            TEST_FILES_PATH . 'BankAccount-cobertura-line.xml',
            $report
        );

        $this->validateReport($report);
    }

    public function testPathCoverageForBankAccountTest(): void
    {
        $report = (new Cobertura)->process($this->getPathCoverageForBankAccount(), null);

        $this->assertStringMatchesFormatFile(
            TEST_FILES_PATH . 'BankAccount-cobertura-path.xml',
            $report
        );
    }

    public function testCoberturaForFileWithIgnoredLines(): void
    {
        $report = (new Cobertura)->process($this->getCoverageForFileWithIgnoredLines());

        $this->assertStringMatchesFormatFile(
            TEST_FILES_PATH . 'ignored-lines-cobertura.xml',
            $report
        );
    }

    public function testCoberturaForClassWithAnonymousFunction(): void
    {
        $report = (new Cobertura)->process($this->getCoverageForClassWithAnonymousFunction());

        $this->assertStringMatchesFormatFile(
            TEST_FILES_PATH . 'class-with-anonymous-function-cobertura.xml',
            $report
        );
    }

    public function testCoberturaForClassAndOutsideFunction(): void
    {
        $report = (new Cobertura)->process($this->getCoverageForClassWithOutsideFunction());

        $this->assertStringMatchesFormatFile(
            TEST_FILES_PATH . 'class-with-outside-function-cobertura.xml',
            $report
        );
    }

    private function validateReport(string $coberturaReport): void
    {
        $document = (new DOMDocument);
        $this->assertTrue($document->loadXML($coberturaReport));
        $this->assertTrue(@$document->validate());
    }
}
