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

use SebastianBergmann\CodeCoverage\Filter;
use SebastianBergmann\CodeCoverage\TestCase;

final class XdebugDriverTest extends TestCase
{
    protected function setUp(): void
    {
        if (\PHP_SAPI !== 'cli') {
            $this->markTestSkipped('This test requires the PHP commandline interpreter');
        }

        if (!\extension_loaded('xdebug')) {
            $this->markTestSkipped('This test requires the Xdebug extension to be loaded');
        }

        if (!\ini_get('xdebug.coverage_enable')) {
            $this->markTestSkipped('This test requires the Xdebug extension\'s code coverage functionality to be enabled');
        }

        if (!\xdebug_code_coverage_started()) {
            $this->markTestSkipped('This test requires code coverage data collection using Xdebug to be active');
        }
    }

    public function testFilterWorks(): void
    {
        $bankAccount = TEST_FILES_PATH . 'BankAccount.php';

        require $bankAccount;

        $this->assertArrayNotHasKey($bankAccount, \xdebug_get_code_coverage());
    }

    public function testDefaultValueOfDeadCodeDetection(): void
    {
        $driver = new XdebugDriver(new Filter);

        $this->assertTrue($driver->detectsDeadCode());
    }

    public function testEnablingDeadCodeDetection(): void
    {
        $driver = new XdebugDriver(new Filter);

        $driver->enableDeadCodeDetection();

        $this->assertTrue($driver->detectsDeadCode());
    }

    public function testDisablingDeadCodeDetection(): void
    {
        $driver = new XdebugDriver(new Filter);

        $driver->disableDeadCodeDetection();

        $this->assertFalse($driver->detectsDeadCode());
    }

    public function testEnablingBranchAndPathCoverage(): void
    {
        $driver = new XdebugDriver(new Filter);

        $driver->enableBranchAndPathCoverage();

        $this->assertTrue($driver->collectsBranchAndPathCoverage());
    }

    public function testDisablingBranchAndPathCoverage(): void
    {
        $driver = new XdebugDriver(new Filter);

        $driver->disableBranchAndPathCoverage();

        $this->assertFalse($driver->collectsBranchAndPathCoverage());
    }
}
