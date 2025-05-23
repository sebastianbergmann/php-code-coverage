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

#[CoversClass(Clover::class)]
final class CloverTest extends TestCase
{
    public function testLineCoverageForBankAccountTest(): void
    {
        $clover = new Clover;

        $this->assertStringMatchesFormatFile(
            TEST_FILES_PATH . 'Report/Clover/BankAccount-line.xml',
            $clover->process($this->getLineCoverageForBankAccount(), null, 'BankAccount'),
        );
    }

    public function testPathCoverageForBankAccountTest(): void
    {
        $clover = new Clover;

        $this->assertStringMatchesFormatFile(
            TEST_FILES_PATH . 'Report/Clover/BankAccount-path.xml',
            $clover->process($this->getPathCoverageForBankAccount(), null, 'BankAccount'),
        );
    }

    public function testCloverForFileWithIgnoredLines(): void
    {
        $clover = new Clover;

        $this->assertStringMatchesFormatFile(
            TEST_FILES_PATH . 'Report/Clover/ignored-lines.xml',
            $clover->process($this->getCoverageForFileWithIgnoredLines()),
        );
    }

    public function testCloverForClassWithAnonymousFunction(): void
    {
        $clover = new Clover;

        $this->assertStringMatchesFormatFile(
            TEST_FILES_PATH . 'Report/Clover/class-with-anonymous-function.xml',
            $clover->process($this->getCoverageForClassWithAnonymousFunction()),
        );
    }
}
