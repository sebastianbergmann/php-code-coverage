<?php declare(strict_types=1);
/*
 * This file is part of phpunit/php-code-coverage.
 *
 * (c) Sebastian Bergmann <sebastian@phpunit.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace SebastianBergmann\CodeCoverage;

use SebastianBergmann\CodeCoverage\Driver\PHPDBG;
use SebastianBergmann\Environment\Runtime;

class PHPDBGTest extends TestCase
{
    protected function setUp(): void
    {
        $runtime = new Runtime;

        if (!$runtime->hasPHPDBGCodeCoverage()) {
            $this->markTestSkipped('This test is only applicable to PHPDBG');
        }
    }

    public function testDefaultValueOfDeadCodeDetection(): void
    {
        $driver = new PHPDBG();

        $this->assertFalse($driver->detectingDeadCode());
    }

    public function testEnablingDeadCodeDetection(): void
    {
        $this->expectException(DeadCodeDetectionNotSupportedException::class);

        $driver = new PHPDBG();

        $driver->detectDeadCode(true);
    }

    public function testDisablingDeadCodeDetection(): void
    {
        $driver = new PHPDBG();

        $driver->detectDeadCode(false);
        $this->assertFalse($driver->detectingDeadCode());
    }

    public function testEnablingBranchAndPathCoverage(): void
    {
        $this->expectException(BranchAndPathCoverageNotSupportedException::class);

        $driver = new PHPDBG();

        $driver->collectBranchAndPathCoverage(true);
        $this->assertTrue($driver->collectingBranchAndPathCoverage());
    }

    public function testDisablingBranchAndPathCoverage(): void
    {
        $driver = new PHPDBG();

        $driver->collectBranchAndPathCoverage(false);
        $this->assertFalse($driver->collectingBranchAndPathCoverage());
    }
}
