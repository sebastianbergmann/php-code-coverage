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

use PHPUnit\Framework\Attributes\CoversClass;
use SebastianBergmann\CodeCoverage\TestCase;

#[CoversClass(Cobertura::class)]
final class CoberturaTest extends TestCase
{
    public function testLineCoverageForBankAccountTest(): void
    {
        $cobertura = new Cobertura;

        $this->assertStringMatchesFormatFile(
            TEST_FILES_PATH . 'Report/Cobertura/BankAccount-line.xml',
            $cobertura->process($this->getLineCoverageForBankAccount(), null),
        );
    }

    public function testPathCoverageForBankAccountTest(): void
    {
        $cobertura = new Cobertura;

        $this->assertStringMatchesFormatFile(
            TEST_FILES_PATH . 'Report/Cobertura/BankAccount-path.xml',
            $cobertura->process($this->getPathCoverageForBankAccount(), null),
        );
    }

    public function testCoberturaForFileWithIgnoredLines(): void
    {
        $cobertura = new Cobertura;

        $this->assertStringMatchesFormatFile(
            TEST_FILES_PATH . 'Report/Cobertura/ignored-lines.xml',
            $cobertura->process($this->getCoverageForFileWithIgnoredLines()),
        );
    }

    public function testCoberturaForClassWithAnonymousFunction(): void
    {
        $cobertura = new Cobertura;

        $this->assertStringMatchesFormatFile(
            TEST_FILES_PATH . 'Report/Cobertura/class-with-anonymous-function.xml',
            $cobertura->process($this->getCoverageForClassWithAnonymousFunction()),
        );
    }

    public function testCoberturaForClassAndOutsideFunction(): void
    {
        $cobertura = new Cobertura;

        $this->assertStringMatchesFormatFile(
            TEST_FILES_PATH . 'Report/Cobertura/class-with-outside-function.xml',
            $cobertura->process($this->getCoverageForClassWithOutsideFunction()),
        );
    }
}
