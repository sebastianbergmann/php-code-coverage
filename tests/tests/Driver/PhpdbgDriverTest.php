<?php declare(strict_types=1);
/*
 * This file is part of phpunit/php-code-coverage.
 *
 * (c) Sebastian Bergmann <sebastian@phpunit.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace SebastianBergmann\CodeCoverage\Driver;

use const PHP_SAPI;
use SebastianBergmann\CodeCoverage\BranchAndPathCoverageNotSupportedException;
use SebastianBergmann\CodeCoverage\DeadCodeDetectionNotSupportedException;
use SebastianBergmann\CodeCoverage\TestCase;

final class PhpdbgDriverTest extends TestCase
{
    protected function setUp(): void
    {
        if (PHP_SAPI !== 'phpdbg') {
            $this->markTestSkipped('This test requires the PHPDBG commandline interpreter');
        }
    }

    public function testDoesNotSupportBranchAndPathCoverage(): void
    {
        $this->assertFalse($this->driver()->canCollectBranchAndPathCoverage());
    }

    public function testBranchAndPathCoverageCanBeDisabled(): void
    {
        $driver = $this->driver();

        $driver->disableBranchAndPathCoverage();

        $this->assertFalse($driver->collectsBranchAndPathCoverage());
    }

    public function testBranchAndPathCoverageCannotBeEnabled(): void
    {
        $this->expectException(BranchAndPathCoverageNotSupportedException::class);

        $this->driver()->enableBranchAndPathCoverage();
    }

    public function testBranchAndPathCoverageIsNotCollected(): void
    {
        $this->assertFalse($this->driver()->collectsBranchAndPathCoverage());
    }

    public function testDoesNotSupportDeadCodeDetection(): void
    {
        $this->assertFalse($this->driver()->canDetectDeadCode());
    }

    public function testDeadCodeDetectionCanBeDisabled(): void
    {
        $driver = $this->driver();

        $driver->disableDeadCodeDetection();

        $this->assertFalse($driver->detectsDeadCode());
    }

    public function testDeadCodeDetectionCannotBeEnabled(): void
    {
        $this->expectException(DeadCodeDetectionNotSupportedException::class);

        $this->driver()->enableDeadCodeDetection();
    }

    public function testDeadCodeIsNotDetected(): void
    {
        $this->assertFalse($this->driver()->detectsDeadCode());
    }

    public function testHasNameAndVersion(): void
    {
        $this->assertStringMatchesFormat('PHPDBG %s', $this->driver()->nameAndVersion());
    }

    private function driver(): PhpdbgDriver
    {
        return new PhpdbgDriver;
    }
}
