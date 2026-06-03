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

use const DIRECTORY_SEPARATOR;
use const PHP_EOL;
use function libxml_clear_errors;
use function libxml_get_errors;
use function libxml_use_internal_errors;
use function realpath;
use function sprintf;
use function str_replace;
use function trim;
use DOMDocument;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Medium;
use SebastianBergmann\CodeCoverage\InMemoryTarget;
use SebastianBergmann\CodeCoverage\TestCase;
use SebastianBergmann\CodeCoverage\Util\Xml;

#[CoversClass(Cobertura::class)]
#[CoversClass(Xml::class)]
#[Medium]
final class CoberturaTest extends TestCase
{
    public function testLineCoverageForBankAccountTest(): void
    {
        $cobertura = new Cobertura;

        $this->assertAndValidate(
            TEST_FILES_PATH . 'Report/Cobertura/BankAccount-line.xml',
            $cobertura->process($this->getLineCoverageForBankAccount()->getReport(), null),
        );
    }

    public function testPathCoverageForBankAccountTest(): void
    {
        $cobertura = new Cobertura;

        $this->assertAndValidate(
            TEST_FILES_PATH . 'Report/Cobertura/BankAccount-path.xml',
            $cobertura->process($this->getPathCoverageForBankAccount()->getReport(), null),
        );
    }

    public function testCoberturaForFileWithIgnoredLines(): void
    {
        $cobertura = new Cobertura;

        $this->assertAndValidate(
            TEST_FILES_PATH . 'Report/Cobertura/ignored-lines.xml',
            $cobertura->process($this->getCoverageForFileWithIgnoredLines()->getReport()),
        );
    }

    public function testCoberturaForClassWithAnonymousFunction(): void
    {
        $cobertura = new Cobertura;

        $this->assertAndValidate(
            TEST_FILES_PATH . 'Report/Cobertura/class-with-anonymous-function.xml',
            $cobertura->process($this->getCoverageForClassWithAnonymousFunction()->getReport()),
        );
    }

    public function testCoberturaForClassAndOutsideFunction(): void
    {
        $cobertura = new Cobertura;

        $this->assertAndValidate(
            TEST_FILES_PATH . 'Report/Cobertura/class-with-outside-function.xml',
            $cobertura->process($this->getCoverageForClassWithOutsideFunction()->getReport()),
        );
    }

    public function testCoberturaForReportWithNestedDirectories(): void
    {
        $cobertura = new Cobertura;

        $report = $cobertura->process($this->reportForNestedDirectories());

        $this->assertStringContainsString('BankAccount.php', $report);
        $this->assertStringContainsString('TargetClass.php', $report);
    }

    public function testWritesReportToTarget(): void
    {
        $cobertura = new Cobertura;
        $target    = InMemoryTarget::target('cobertura');

        $buffer = $cobertura->process($this->getLineCoverageForBankAccount()->getReport(), $target);

        $this->assertSame($buffer, InMemoryTarget::content($target));
    }

    /**
     * @param non-empty-string $expectationFile
     * @param non-empty-string $coberturaXml
     */
    private function assertAndValidate(string $expectationFile, string $coberturaXml): void
    {
        $this->assertStringMatchesFormatFile($expectationFile, $coberturaXml);

        $dtdPath = realpath(__DIR__ . '/../../_files/Report/Cobertura/coverage-04.dtd');

        if (DIRECTORY_SEPARATOR === '\\') {
            $dtdPath = 'file:///' . str_replace('\\', '/', $dtdPath);
        }

        $coberturaXml = str_replace(
            '<?xml version="1.0" encoding="UTF-8"?>',
            '<?xml version="1.0" encoding="UTF-8"?>' . "\n" . '<!DOCTYPE coverage SYSTEM "' . $dtdPath . '">',
            $coberturaXml,
        );

        libxml_use_internal_errors(true);

        $document = new DOMDocument;
        $document->loadXML($coberturaXml);

        if (!$document->validate()) {
            $buffer = 'Generated XML document does not validate against Cobertura DTD:' . PHP_EOL . PHP_EOL;

            foreach (libxml_get_errors() as $error) {
                $buffer .= sprintf(
                    '- Line %d: %s' . PHP_EOL,
                    $error->line,
                    trim($error->message),
                );
            }

            $buffer .= PHP_EOL;
        }

        libxml_clear_errors();

        if (isset($buffer)) {
            $this->fail($buffer);
        }
    }
}
