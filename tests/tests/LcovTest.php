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
 * @covers \SebastianBergmann\CodeCoverage\Report\Lcov
 */
final class LcovTest extends TestCase
{
    public function testLineCoverageForBankAccountTest(): void
    {
        $lcov = new Lcov;

        $this->assertStringMatchesFormatFile(
            TEST_FILES_PATH . 'BankAccount-lcov-line.info',
            $lcov->process($this->getLineCoverageForBankAccount(), null, 'BankAccount')
        );
    }

    public function testPathCoverageForBankAccountTest(): void
    {
        $lcov = new Lcov;

        $this->assertStringMatchesFormatFile(
            TEST_FILES_PATH . 'BankAccount-lcov-path.info',
            $lcov->process($this->getPathCoverageForBankAccount(), null, 'BankAccount')
        );
    }

    public function testCloverForFileWithIgnoredLines(): void
    {
        $lcov = new Lcov;

        $this->assertStringMatchesFormatFile(
            TEST_FILES_PATH . 'ignored-lines-lcov.info',
            $lcov->process($this->getCoverageForFileWithIgnoredLines())
        );
    }

    public function testCloverForClassWithAnonymousFunction(): void
    {
        $lcov = new Lcov;

        $this->assertStringMatchesFormatFile(
            TEST_FILES_PATH . 'class-with-anonymous-function-lcov.info',
            $lcov->process($this->getCoverageForClassWithAnonymousFunction())
        );
    }
}
