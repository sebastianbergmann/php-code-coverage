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

use function array_values;
use SebastianBergmann\CodeCoverage\Data\RawCodeCoverageData;
use SebastianBergmann\CodeCoverage\Driver\Driver;

/**
 * Emulates the Xdebug driver by returning prepared raw code coverage data,
 * one element per call to stop().
 */
final class FakeDriver extends Driver
{
    /**
     * @var list<RawCodeCoverageData>
     */
    private array $coveragePerStop;
    private int $stops = 0;

    public function __construct(RawCodeCoverageData ...$coveragePerStop)
    {
        $this->coveragePerStop = array_values($coveragePerStop);
    }

    public function name(): string
    {
        return 'Xdebug';
    }

    public function version(): string
    {
        return '3.0.0';
    }

    public function start(): void
    {
    }

    public function stop(): RawCodeCoverageData
    {
        $coverage = $this->coveragePerStop[$this->stops];

        $this->stops++;

        return $coverage;
    }

    protected function canCollectBranchCoverage(): bool
    {
        return true;
    }

    protected function canCollectPathCoverage(): bool
    {
        return true;
    }
}
