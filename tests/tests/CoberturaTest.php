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
 * @covers \SebastianBergmann\CodeCoverage\Report\Cobertura
 */
class CoberturaTest extends TestCase
{
    public function testCloverForBankAccountTest(): void
    {
        $cobertura = new Cobertura;

        $this->assertStringMatchesFormatFile(
            TEST_FILES_PATH . 'BankAccount-cobertura.xml',
            $cobertura->process($this->getCoverageForBankAccount(), 'BankAccount')
        );
    }

    public function testCloverForFileWithIgnoredLines(): void
    {
        $this->markTestIncomplete();

        $cobertura = new Cobertura;

        $this->assertStringMatchesFormatFile(
            TEST_FILES_PATH . 'ignored-lines-cobertura.xml',
            $cobertura->process($this->getCoverageForFileWithIgnoredLines(), 'BankAccount')
        );
    }

    public function testCloverForClassWithAnonymousFunction(): void
    {
        $this->markTestIncomplete();

        $cobertura = new Cobertura;

        $this->assertStringMatchesFormatFile(
            TEST_FILES_PATH . 'class-with-anonymous-function-cobertura.xml',
            $cobertura->process($this->getCoverageForClassWithAnonymousFunction(), 'BankAccount')
        );
    }
}
