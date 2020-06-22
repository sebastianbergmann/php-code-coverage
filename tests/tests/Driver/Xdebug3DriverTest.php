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
use function extension_loaded;
use function ini_get;
use function phpversion;
use function version_compare;
use function xdebug_code_coverage_started;
use function xdebug_get_code_coverage;
use SebastianBergmann\CodeCoverage\Filter;
use SebastianBergmann\CodeCoverage\TestCase;

final class Xdebug3DriverTest extends TestCase
{
    protected function setUp(): void
    {
        if (PHP_SAPI !== 'cli') {
            $this->markTestSkipped('This test requires the PHP commandline interpreter');
        }

        if (!extension_loaded('xdebug')) {
            $this->markTestSkipped('This test requires the Xdebug extension to be loaded');
        }

        if (version_compare(phpversion('xdebug'), '3', '<')) {
            $this->markTestSkipped('This test requires version 3 of the Xdebug extension to be loaded');
        }

        if (!ini_get('xdebug.mode') || ini_get('xdebug.mode') !== 'coverage') {
            $this->markTestSkipped('This test requires the Xdebug extension\'s code coverage functionality to be enabled');
        }

        if (!xdebug_code_coverage_started()) {
            $this->markTestSkipped('This test requires code coverage data collection using Xdebug to be active');
        }
    }

    public function testSupportsBranchAndPathCoverage(): void
    {
        $this->assertTrue($this->driver()->canCollectBranchAndPathCoverage());
    }

    public function testBranchAndPathCoverageCanBeDisabled(): void
    {
        $driver = $this->driver();

        $driver->disableBranchAndPathCoverage();

        $this->assertFalse($driver->collectsBranchAndPathCoverage());
    }

    public function testBranchAndPathCoverageCanBeEnabled(): void
    {
        $driver = $this->driver();

        $driver->enableBranchAndPathCoverage();

        $this->assertTrue($driver->collectsBranchAndPathCoverage());
    }

    public function testBranchAndPathCoverageIsNotCollectedByDefault(): void
    {
        $this->assertFalse($this->driver()->collectsBranchAndPathCoverage());
    }

    public function testSupportsDeadCodeDetection(): void
    {
        $this->assertTrue($this->driver()->canDetectDeadCode());
    }

    public function testDeadCodeDetectionCanBeDisabled(): void
    {
        $driver = $this->driver();

        $driver->disableDeadCodeDetection();

        $this->assertFalse($driver->detectsDeadCode());
    }

    public function testDeadCodeDetectionCanBeEnabled(): void
    {
        $driver = $this->driver();

        $driver->enableDeadCodeDetection();

        $this->assertTrue($driver->detectsDeadCode());
    }

    public function testDeadCodeIsNotDetectedByDefault(): void
    {
        $this->assertFalse($this->driver()->detectsDeadCode());
    }

    public function testHasNameAndVersion(): void
    {
        $this->assertStringMatchesFormat('Xdebug %s', $this->driver()->nameAndVersion());
    }

    public function testFilterWorks(): void
    {
        $bankAccount = TEST_FILES_PATH . 'BankAccount.php';

        require $bankAccount;

        $this->assertArrayNotHasKey($bankAccount, xdebug_get_code_coverage());
    }

    private function driver(): Xdebug3Driver
    {
        return new Xdebug3Driver(new Filter);
    }
}
