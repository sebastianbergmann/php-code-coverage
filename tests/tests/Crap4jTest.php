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
 * @covers \SebastianBergmann\CodeCoverage\Report\Crap4j
 */
final class Crap4jTest extends TestCase
{
    public function testForBankAccountTest(): void
    {
        $crap4j = new Crap4j;

        $this->assertStringMatchesFormatFile(
            TEST_FILES_PATH . 'BankAccount-crap4j.xml',
            $crap4j->process($this->getLineCoverageForBankAccount(), null, 'BankAccount')
        );
    }

    public function testForFileWithIgnoredLines(): void
    {
        $crap4j = new Crap4j;

        $this->assertStringMatchesFormatFile(
            TEST_FILES_PATH . 'ignored-lines-crap4j.xml',
            $crap4j->process($this->getCoverageForFileWithIgnoredLines(), null, 'CoverageForFileWithIgnoredLines')
        );
    }

    public function testForClassWithAnonymousFunction(): void
    {
        $crap4j = new Crap4j;

        $this->assertStringMatchesFormatFile(
            TEST_FILES_PATH . 'class-with-anonymous-function-crap4j.xml',
            $crap4j->process($this->getCoverageForClassWithAnonymousFunction(), null, 'CoverageForClassWithAnonymousFunction')
        );
    }
}
