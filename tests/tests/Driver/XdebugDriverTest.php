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

use const PHP_BINARY;
use const PHP_SAPI;
use function escapeshellarg;
use function extension_loaded;
use function in_array;
use function is_array;
use function json_decode;
use function phpversion;
use function putenv;
use function shell_exec;
use function sprintf;
use function version_compare;
use function xdebug_get_code_coverage;
use function xdebug_info;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Medium;
use SebastianBergmann\CodeCoverage\Filter;
use SebastianBergmann\CodeCoverage\TestCase;

#[CoversClass(XdebugDriver::class)]
#[Medium]
final class XdebugDriverTest extends TestCase
{
    protected function setUp(): void
    {
        if (PHP_SAPI !== 'cli') {
            $this->markTestSkipped('This test requires the PHP commandline interpreter');
        }

        $version = phpversion('xdebug');

        if (!extension_loaded('xdebug') || $version === false || !version_compare($version, '3.1', '>=')) {
            $this->markTestSkipped('This test requires the Xdebug extension (version >= 3.1) to be loaded');
        }

        if (!in_array('coverage', xdebug_info('mode'), true)) {
            $this->markTestSkipped('This test requires code coverage data collection using Xdebug to be enabled');
        }
    }

    public function testDefaultsToLineGranularity(): void
    {
        $this->assertSame(Granularity::Line, $this->driver()->granularity());
    }

    public function testGranularityCanBeSetToLineAndBranch(): void
    {
        $driver = $this->driver();

        $driver->setGranularity(Granularity::LineAndBranch);

        $this->assertSame(Granularity::LineAndBranch, $driver->granularity());
    }

    public function testGranularityCanBeSetToLineBranchAndPath(): void
    {
        $driver = $this->driver();

        $driver->setGranularity(Granularity::LineBranchAndPath);

        $this->assertSame(Granularity::LineBranchAndPath, $driver->granularity());
    }

    public function testHasNameAndVersion(): void
    {
        $this->assertStringMatchesFormat('Xdebug %s', $this->driver()->nameAndVersion());
    }

    public function testDoesNotCollectHitCounts(): void
    {
        $this->assertFalse($this->driver()->collectsHitCounts());
    }

    public function testReportsLinesThatDependOnCompilation(): void
    {
        $this->assertTrue($this->driver()->linesDependOnCompilation());
    }

    public function testFilterWorks(): void
    {
        $bankAccount = TEST_FILES_PATH . 'BankAccount.php';

        require $bankAccount;

        $this->assertArrayNotHasKey($bankAccount, xdebug_get_code_coverage());
    }

    public function testCollectsOnlyLineCoverageUsingLineGranularity(): void
    {
        $this->assertSame(
            [
                'lines'    => true,
                'branches' => false,
                'paths'    => false,
            ],
            $this->collectCoverage(Granularity::Line),
        );
    }

    /**
     * Xdebug has no mode that collects branch coverage without also collecting
     * path coverage; the path coverage data must therefore be discarded.
     */
    public function testCollectsBranchCoverageButNotPathCoverageUsingLineAndBranchGranularity(): void
    {
        $this->assertSame(
            [
                'lines'    => true,
                'branches' => true,
                'paths'    => false,
            ],
            $this->collectCoverage(Granularity::LineAndBranch),
        );
    }

    public function testCollectsBranchAndPathCoverageUsingLineBranchAndPathGranularity(): void
    {
        $this->assertSame(
            [
                'lines'    => true,
                'branches' => true,
                'paths'    => true,
            ],
            $this->collectCoverage(Granularity::LineBranchAndPath),
        );
    }

    public function testIsLimitedToDirectoryOfFilesInFilterWhenNoOtherFileInItWasLoaded(): void
    {
        $data = $this->collectCoverageUsingFilter('nothing-is-loaded-before');

        /*
         * That the file in filter-directories/src that is not in the filter
         * is reported shows that the driver is limited to that directory
         * instead of to the file in it that is in the filter. The data for
         * the file that is not in the filter is removed when the filter is
         * applied.
         *
         * The file in filter-directories/src-old is not reported.
         */
        $this->assertSame(
            [
                'BankAccount.php',
                'filter-directories/src/a.php',
                'filter-directories/src/b.php',
            ],
            $data['reported'],
        );

        $this->assertSame(
            [
                'BankAccount.php',
                'filter-directories/src/a.php',
            ],
            $data['filtered'],
        );
    }

    public function testIsLimitedToFilesInFilterWhenOtherFileInTheirDirectoryWasLoaded(): void
    {
        $data = $this->collectCoverageUsingFilter('file-that-is-not-included-is-loaded-before');

        /*
         * The file that was loaded before the driver was created is reported
         * because it was compiled before the driver was limited.
         */
        $this->assertSame(
            [
                'BankAccount.php',
                'filter-directories/src/a.php',
                'filter-directories/src/loaded_before.php',
            ],
            $data['reported'],
        );

        $this->assertSame(
            [
                'BankAccount.php',
                'filter-directories/src/a.php',
            ],
            $data['filtered'],
        );
    }

    private function driver(): XdebugDriver
    {
        return new XdebugDriver(new Filter);
    }

    /**
     * @return array{lines: bool, branches: bool, paths: bool}
     */
    private function collectCoverage(Granularity $granularity): array
    {
        putenv('XDEBUG_MODE=coverage');

        $output = shell_exec(
            sprintf(
                '%s -d xdebug.mode=coverage %s %s',
                escapeshellarg(PHP_BINARY),
                escapeshellarg(TEST_FILES_PATH . 'collect_coverage_using_xdebug_driver.php'),
                escapeshellarg($granularity->name),
            ),
        );

        $this->assertIsString($output);

        $data = json_decode($output, true);

        $this->assertTrue(is_array($data), 'Failed collecting code coverage data: ' . $output);

        return [
            'lines'    => (bool) ($data['lines'] ?? false),
            'branches' => (bool) ($data['branches'] ?? false),
            'paths'    => (bool) ($data['paths'] ?? false),
        ];
    }

    /**
     * @param 'file-that-is-not-included-is-loaded-before'|'nothing-is-loaded-before' $scenario
     *
     * @return array{reported: mixed, filtered: mixed}
     */
    private function collectCoverageUsingFilter(string $scenario): array
    {
        putenv('XDEBUG_MODE=coverage');

        $output = shell_exec(
            sprintf(
                '%s -d xdebug.mode=coverage %s %s',
                escapeshellarg(PHP_BINARY),
                escapeshellarg(TEST_FILES_PATH . 'collect_coverage_using_xdebug_driver_and_filter.php'),
                escapeshellarg($scenario),
            ),
        );

        $this->assertIsString($output);

        $data = json_decode($output, true);

        $this->assertTrue(is_array($data), 'Failed collecting code coverage data: ' . $output);

        return [
            'reported' => $data['reported'] ?? null,
            'filtered' => $data['filtered'] ?? null,
        ];
    }
}
