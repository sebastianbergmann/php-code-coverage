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

use SebastianBergmann\CodeCoverage\BranchAndPathCoverageNotSupportedException;
use SebastianBergmann\CodeCoverage\DeadCodeDetectionNotSupportedException;
use SebastianBergmann\CodeCoverage\Filter;
use SebastianBergmann\CodeCoverage\TestCase;
use SebastianBergmann\Environment\Runtime;

final class PcovDriverTest extends TestCase
{
    protected function setUp(): void
    {
        if (!(new Runtime)->hasPCOV()) {
            $this->markTestSkipped('This test is only applicable to PCOV');
        }
    }

    public function testDefaultValueOfDeadCodeDetection(): void
    {
        $driver = new PcovDriver(new Filter);

        $this->assertFalse($driver->detectsDeadCode());
    }

    public function testEnablingDeadCodeDetection(): void
    {
        $this->expectException(DeadCodeDetectionNotSupportedException::class);

        $driver = new PcovDriver(new Filter);

        $driver->enableDeadCodeDetection();
    }

    public function testDisablingDeadCodeDetection(): void
    {
        $driver = new PcovDriver(new Filter);

        $driver->disableDeadCodeDetection();

        $this->assertFalse($driver->detectsDeadCode());
    }

    public function testEnablingBranchAndPathCoverage(): void
    {
        $this->expectException(BranchAndPathCoverageNotSupportedException::class);

        $driver = new PcovDriver(new Filter);

        $driver->enableBranchAndPathCoverage();

        $this->assertTrue($driver->collectsBranchAndPathCoverage());
    }

    public function testDisablingBranchAndPathCoverage(): void
    {
        $driver = new PcovDriver(new Filter);

        $driver->disableBranchAndPathCoverage();

        $this->assertFalse($driver->collectsBranchAndPathCoverage());
    }
}
