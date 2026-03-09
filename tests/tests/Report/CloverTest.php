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

#[CoversClass(Clover::class)]
#[CoversClass(Xml::class)]
final class CloverTest extends TestCase
{
    public function testLineCoverageForBankAccountTest(): void
    {
        $clover = new Clover;

        $this->assertStringMatchesFormatFile(
            TEST_FILES_PATH . 'Report/Clover/BankAccount-line.xml',
            $clover->process($this->getLineCoverageForBankAccount()->getReport(), null, 'BankAccount'),
        );
    }

    public function testPathCoverageForBankAccountTest(): void
    {
        $clover = new Clover;

        $this->assertStringMatchesFormatFile(
            TEST_FILES_PATH . 'Report/Clover/BankAccount-path.xml',
            $clover->process($this->getPathCoverageForBankAccount()->getReport(), null, 'BankAccount'),
        );
    }

    public function testCloverForFileWithIgnoredLines(): void
    {
        $clover = new Clover;

        $this->assertStringMatchesFormatFile(
            TEST_FILES_PATH . 'Report/Clover/ignored-lines.xml',
            $clover->process($this->getCoverageForFileWithIgnoredLines()->getReport()),
        );
    }

    public function testCloverForClassWithAnonymousFunction(): void
    {
        $clover = new Clover;

        $this->assertStringMatchesFormatFile(
            TEST_FILES_PATH . 'Report/Clover/class-with-anonymous-function.xml',
            $clover->process($this->getCoverageForClassWithAnonymousFunction()->getReport()),
        );
    }
}
