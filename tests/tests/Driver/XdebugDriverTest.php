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
use function in_array;
use function phpversion;
use function version_compare;
use function xdebug_get_code_coverage;
use function xdebug_info;
use SebastianBergmann\CodeCoverage\Filter;
use SebastianBergmann\CodeCoverage\TestCase;

final class XdebugDriverTest extends TestCase
{
    protected function setUp(): void
    {
        if (PHP_SAPI !== 'cli') {
            $this->markTestSkipped('This test requires the PHP commandline interpreter');
        }

        if (!extension_loaded('xdebug') || !version_compare(phpversion('xdebug'), '3.1', '>=')) {
            $this->markTestSkipped('This test requires the Xdebug extension (version >= 3.1) to be loaded');
        }

        if (!in_array('coverage', xdebug_info('mode'), true)) {
            $this->markTestSkipped('This test requires code coverage data collection using Xdebug to be enabled');
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

    private function driver(): XdebugDriver
    {
        return new XdebugDriver(new Filter);
    }
}
