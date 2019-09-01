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
 * @covers SebastianBergmann\CodeCoverage\Report\Clover
 */
class CloverTest extends TestCase
{
    public function testCloverForBankAccountTest(): void
    {
        $clover = new Clover;

        $this->assertStringMatchesFormatFile(
            TEST_FILES_PATH . 'BankAccount-clover.xml',
            $clover->process($this->getCoverageForBankAccount(), null, 'BankAccount')
        );
    }

    public function testCloverForFileWithIgnoredLines(): void
    {
        $clover = new Clover;

        $this->assertStringMatchesFormatFile(
            TEST_FILES_PATH . 'ignored-lines-clover.xml',
            $clover->process($this->getCoverageForFileWithIgnoredLines())
        );
    }

    public function testCloverForClassWithAnonymousFunction(): void
    {
        $clover = new Clover;

        $this->assertStringMatchesFormatFile(
            TEST_FILES_PATH . 'class-with-anonymous-function-clover.xml',
            $clover->process($this->getCoverageForClassWithAnonymousFunction())
        );
    }

    public function testCloverThrowsRuntimeExceptionWhenUnableToWriteToTarget(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Could not write to "stdout://"');

        $clover = new Clover;
        $clover->process($this->getCoverageForBankAccount(), 'stdout://');
    }

    public function testCloverThrowsRuntimeExceptionWhenTargetDirCouldNotBeCreated(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Directory "/foo/bar" was not created');

        $clover = new Clover;
        $clover->process($this->getCoverageForBankAccount(), '/foo/bar/baz');
    }
}
