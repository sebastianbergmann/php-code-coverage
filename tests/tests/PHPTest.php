<?php declare(strict_types=1);
/*
 * This file is part of the php-code-coverage package.
 *
 * (c) Sebastian Bergmann <sebastian@phpunit.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace SebastianBergmann\CodeCoverage\Report;

use SebastianBergmann\CodeCoverage\RuntimeException;
use SebastianBergmann\CodeCoverage\TestCase;

/**
 * @covers SebastianBergmann\CodeCoverage\Report\PHP
 */
class PHPTest extends TestCase
{
    public function testPHPForBankAccountTest(): void
    {
        $report = new PHP();

        $this->assertStringMatchesFormatFile(
            TEST_FILES_PATH . 'BankAccount.php.txt',
            \str_replace(\PHP_EOL, "\n", $report->process($this->getCoverageForBankAccount()))
        );
    }

    public function testReportThrowsRuntimeExceptionWhenUnableToWriteToTarget(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Could not write to "stdin://"');

        $report = new PHP();
        $report->process($this->getCoverageForBankAccount(), 'stdin://');
    }

    public function testReportThrowsRuntimeExceptionWhenTargetDirCouldNotBeCreated(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Directory "/foo/bar" was not created');

        $report = new PHP();
        $report->process($this->getCoverageForBankAccount(), '/foo/bar/baz');
    }
}
