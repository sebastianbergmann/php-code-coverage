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
use SebastianBergmann\CodeCoverage\Util\Xml;

#[CoversClass(Crap4j::class)]
#[CoversClass(Xml::class)]
final class Crap4jTest extends TestCase
{
    public function testForBankAccountTest(): void
    {
        $crap4j = new Crap4j;

        $this->assertStringMatchesFormatFile(
            TEST_FILES_PATH . 'Report/Crap4j/BankAccount.xml',
            $crap4j->process($this->getLineCoverageForBankAccount()->getReport(), null, 'BankAccount'),
        );
    }

    public function testForFileWithIgnoredLines(): void
    {
        $crap4j = new Crap4j;

        $this->assertStringMatchesFormatFile(
            TEST_FILES_PATH . 'Report/Crap4j/ignored-lines.xml',
            $crap4j->process($this->getCoverageForFileWithIgnoredLines()->getReport(), null, 'CoverageForFileWithIgnoredLines'),
        );
    }

    public function testForClassWithAnonymousFunction(): void
    {
        $crap4j = new Crap4j;

        $this->assertStringMatchesFormatFile(
            TEST_FILES_PATH . 'Report/Crap4j/class-with-anonymous-function.xml',
            $crap4j->process($this->getCoverageForClassWithAnonymousFunction()->getReport(), null, 'CoverageForClassWithAnonymousFunction'),
        );
    }
}
