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
use SebastianBergmann\CodeCoverage\BranchAndPathCoverageNotSupportedException;
use SebastianBergmann\CodeCoverage\Filter;
use SebastianBergmann\CodeCoverage\TestCase;

final class PcovDriverTest extends TestCase
{
    protected function setUp(): void
    {
        if (PHP_SAPI !== 'cli') {
            $this->markTestSkipped('This test requires the PHP commandline interpreter');
        }

        if (!extension_loaded('pcov')) {
            $this->markTestSkipped('This test requires the PCOV extension to be loaded');
        }

        if (!ini_get('pcov.enabled')) {
            $this->markTestSkipped('This test requires the PCOV extension to be enabled');
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

    public function testHasNameAndVersion(): void
    {
        $this->assertStringMatchesFormat('PCOV %s', $this->driver()->nameAndVersion());
    }

    private function driver(): PcovDriver
    {
        return new PcovDriver(new Filter);
    }
}
