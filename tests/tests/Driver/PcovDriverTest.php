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
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Medium;
use SebastianBergmann\CodeCoverage\BranchCoverageNotSupportedException;
use SebastianBergmann\CodeCoverage\Filter;
use SebastianBergmann\CodeCoverage\TestCase;

#[CoversClass(PcovDriver::class)]
#[Medium]
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

        $enabled = ini_get('pcov.enabled');

        if ($enabled === false || $enabled === '' || $enabled === '0') {
            $this->markTestSkipped('This test requires the PCOV extension to be enabled');
        }
    }

    public function testDefaultsToLineGranularity(): void
    {
        $this->assertSame(Granularity::Line, $this->driver()->granularity());
    }

    public function testGranularityCanBeSetToLine(): void
    {
        $driver = $this->driver();

        $driver->setGranularity(Granularity::Line);

        $this->assertSame(Granularity::Line, $driver->granularity());
    }

    public function testCannotSetGranularityRequiringBranchCoverage(): void
    {
        $this->expectException(BranchCoverageNotSupportedException::class);

        $this->driver()->setGranularity(Granularity::LineAndBranch);
    }

    public function testCannotSetGranularityRequiringPathCoverage(): void
    {
        $this->expectException(BranchCoverageNotSupportedException::class);

        $this->driver()->setGranularity(Granularity::LineBranchAndPath);
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
