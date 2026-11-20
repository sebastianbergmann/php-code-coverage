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

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Small;
use PHPUnit\Framework\TestCase;
use SebastianBergmann\CodeCoverage\BranchCoverageNotSupportedException;
use SebastianBergmann\CodeCoverage\Data\RawCodeCoverageData;
use SebastianBergmann\CodeCoverage\PathCoverageNotSupportedException;

#[CoversClass(Driver::class)]
#[Small]
final class DriverTest extends TestCase
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

    public function testDefaultsToLineGranularity(): void
    {
        $this->assertSame(Granularity::Line, $this->driverThatOnlySupportsLineCoverage()->granularity());
    }

    public function testHasNameAndVersion(): void
    {
        $this->assertSame('Driver 1.2.3', $this->driverThatOnlySupportsLineCoverage()->nameAndVersion());
    }

    public function testDoesNotCollectHitCountsByDefault(): void
    {
        $this->assertFalse($this->driverThatOnlySupportsLineCoverage()->collectsHitCounts());
    }

    public function testReportsLinesThatDependOnCompilationByDefault(): void
    {
        $this->assertTrue($this->driverThatOnlySupportsLineCoverage()->linesDependOnCompilation());
    }

    public function testGranularityCanBeSetToLineWhenBranchCoverageIsNotSupported(): void
    {
        $driver = $this->driverThatOnlySupportsLineCoverage();

        $driver->setGranularity(Granularity::Line);

        $this->assertSame(Granularity::Line, $driver->granularity());
    }

    #[DataProvider('granularitiesThatRequireBranchCoverage')]
    public function testDoesNotSupportBranchCoverageByDefault(Granularity $granularity): void
    {
        $driver = $this->driverThatOnlySupportsLineCoverage();

        try {
            $driver->setGranularity($granularity);
        } catch (BranchCoverageNotSupportedException $e) {
            $this->assertSame('Driver 1.2.3 does not support branch coverage', $e->getMessage());
            $this->assertSame(Granularity::Line, $driver->granularity());

            return;
        }

        $this->fail('Expected exception was not thrown');
    }

    public function testGranularityCanBeSetToLineAndBranchWhenPathCoverageIsNotSupported(): void
    {
        $driver = $this->driverThatSupportsBranchCoverageButNotPathCoverage();

        $driver->setGranularity(Granularity::LineAndBranch);

        $this->assertSame(Granularity::LineAndBranch, $driver->granularity());
    }

    public function testDoesNotSupportPathCoverageByDefault(): void
    {
        $driver = $this->driverThatSupportsBranchCoverageButNotPathCoverage();

        try {
            $driver->setGranularity(Granularity::LineBranchAndPath);
        } catch (PathCoverageNotSupportedException $e) {
            $this->assertSame('Driver 1.2.3 does not support path coverage', $e->getMessage());
            $this->assertSame(Granularity::Line, $driver->granularity());

            return;
        }

        $this->fail('Expected exception was not thrown');
    }

    /**
     * The driver does not override canCollectBranchCoverage() and canCollectPathCoverage().
     */
    private function driverThatOnlySupportsLineCoverage(): Driver
    {
        return new class extends Driver
        {
            public function name(): string
            {
                return 'Driver';
            }

            public function version(): string
            {
                return '1.2.3';
            }

            public function start(): void
            {
            }

            public function stop(): RawCodeCoverageData
            {
                return RawCodeCoverageData::fromLineCoverage([]);
            }
        };
    }

    /**
     * The driver does not override canCollectPathCoverage().
     */
    private function driverThatSupportsBranchCoverageButNotPathCoverage(): Driver
    {
        return new class extends Driver
        {
            public function name(): string
            {
                return 'Driver';
            }

            public function version(): string
            {
                return '1.2.3';
            }

            public function start(): void
            {
            }

            public function stop(): RawCodeCoverageData
            {
                return RawCodeCoverageData::fromLineCoverage([]);
            }

            protected function canCollectBranchCoverage(): bool
            {
                return true;
            }
        };
    }
}
