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

use function in_array;
use function phpversion;
use function version_compare;
use function xdebug_info;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Medium;
use PHPUnit\Framework\TestCase;
use SebastianBergmann\CodeCoverage\Filter;
use SebastianBergmann\CodeCoverage\NoSupportedDriverAvailableException;
use SebastianBergmann\Environment\Runtime;

#[CoversClass(Selector::class)]
#[Medium]
final class SelectorTest extends TestCase
{
    /**
     * @return non-empty-array<non-empty-string, array{0: Granularity}>
     */
    public static function granularitiesThatRequireBranchCoverage(): array
    {
        return [
            'line and branch'        => [Granularity::LineAndBranch],
            'line, branch, and path' => [Granularity::LineBranchAndPath],
        ];
    }

    public function testSelectsPcovForLineCoverageWhenPcovIsAvailable(): void
    {
        $this->requirePcov();

        $this->assertInstanceOf(PcovDriver::class, (new Selector)->select(new Filter));
    }

    public function testSelectsXdebugForLineCoverageWhenPcovIsNotAvailable(): void
    {
        $this->requireXdebugWithoutPcov();

        $driver = (new Selector)->select(new Filter);

        $this->assertInstanceOf(XdebugDriver::class, $driver);
        $this->assertSame(Granularity::Line, $driver->granularity());
    }

    #[DataProvider('granularitiesThatRequireBranchCoverage')]
    public function testSelectsXdebugForGranularityThatRequiresBranchCoverage(Granularity $granularity): void
    {
        $this->requireXdebug();

        $driver = (new Selector)->select(new Filter, $granularity);

        $this->assertInstanceOf(XdebugDriver::class, $driver);
        $this->assertSame($granularity, $driver->granularity());
    }

    public function testCannotSelectDriverForLineCoverageWhenNeitherPcovNorXdebugIsAvailable(): void
    {
        $runtime = new Runtime;

        if ($runtime->hasPCOV() || $runtime->hasXdebug()) {
            $this->markTestSkipped('This test requires that neither the PCOV extension nor the Xdebug extension is loaded');
        }

        $this->expectException(NoSupportedDriverAvailableException::class);
        $this->expectExceptionMessageIs('No code coverage driver available that supports line coverage');

        (new Selector)->select(new Filter);
    }

    #[DataProvider('granularitiesThatRequireBranchCoverage')]
    public function testCannotSelectDriverForGranularityThatRequiresBranchCoverageWhenXdebugIsNotAvailable(Granularity $granularity): void
    {
        if ((new Runtime)->hasXdebug()) {
            $this->markTestSkipped('This test requires that the Xdebug extension is not loaded');
        }

        $this->expectException(NoSupportedDriverAvailableException::class);

        (new Selector)->select(new Filter, $granularity);
    }

    /**
     * @deprecated Tests deprecated functionality
     */
    public function testSelectsPcovUsingDeprecatedMethodForLineCoverageWhenPcovIsAvailable(): void
    {
        $this->requirePcov();

        $this->assertInstanceOf(PcovDriver::class, (new Selector)->forLineCoverage(new Filter));
    }

    /**
     * @deprecated Tests deprecated functionality
     */
    public function testSelectsXdebugUsingDeprecatedMethodForLineCoverageWhenPcovIsNotAvailable(): void
    {
        $this->requireXdebugWithoutPcov();

        $driver = (new Selector)->forLineCoverage(new Filter);

        $this->assertInstanceOf(XdebugDriver::class, $driver);
        $this->assertSame(Granularity::Line, $driver->granularity());
    }

    /**
     * @deprecated Tests deprecated functionality
     */
    public function testSelectsXdebugUsingDeprecatedMethodForLineAndPathCoverage(): void
    {
        $this->requireXdebug();

        $driver = (new Selector)->forLineAndPathCoverage(new Filter);

        $this->assertInstanceOf(XdebugDriver::class, $driver);
        $this->assertSame(Granularity::LineBranchAndPath, $driver->granularity());
    }

    private function requirePcov(): void
    {
        if (!(new Runtime)->hasPCOV()) {
            $this->markTestSkipped('This test requires the PCOV extension to be loaded and enabled');
        }
    }

    private function requireXdebug(): void
    {
        $version = phpversion('xdebug');

        if (!(new Runtime)->hasXdebug() || $version === false || !version_compare($version, '3.1', '>=')) {
            $this->markTestSkipped('This test requires the Xdebug extension (version >= 3.1) to be loaded');
        }

        if (!in_array('coverage', xdebug_info('mode'), true)) {
            $this->markTestSkipped('This test requires code coverage data collection using Xdebug to be enabled');
        }
    }

    private function requireXdebugWithoutPcov(): void
    {
        if ((new Runtime)->hasPCOV()) {
            $this->markTestSkipped('This test requires that the PCOV extension is not loaded');
        }

        $this->requireXdebug();
    }
}
