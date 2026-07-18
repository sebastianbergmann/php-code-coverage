<?php declare(strict_types=1);
/*
 * This file is part of phpunit/php-code-coverage.
 *
 * (c) Sebastian Bergmann <sebastian@phpunit.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace SebastianBergmann\CodeCoverage\Data;

use function max;
use NoDiscard;
use SebastianBergmann\CodeCoverage\Driver\XdebugDriver;

/**
 * @internal This class is not covered by the backward compatibility promise for phpunit/php-code-coverage
 *
 * @no-named-arguments Parameter names are not covered by the backward compatibility promise for phpunit/php-code-coverage
 *
 * @phpstan-import-type TestIndexType from ProcessedCodeCoverageData
 * @phpstan-import-type XdebugBranchCoverageType from XdebugDriver
 */
final class ProcessedBranchCoverageData
{
    public readonly int $op_start;
    public readonly int $op_end;
    public readonly int $line_start;
    public readonly int $line_end;

    /** @var array<TestIndexType, positive-int> map of test index to the number of times the test traversed the branch */
    public array $hit;

    /** @var array<int, int> */
    public readonly array $out;

    /** @var array<int, int> */
    public readonly array $out_hit;

    /**
     * @param XdebugBranchCoverageType $xdebugCoverageData
     */
    public static function fromXdebugCoverage(array $xdebugCoverageData): self
    {
        return new self(
            $xdebugCoverageData['op_start'],
            $xdebugCoverageData['op_end'],
            $xdebugCoverageData['line_start'],
            $xdebugCoverageData['line_end'],
            [],
            $xdebugCoverageData['out'],
            $xdebugCoverageData['out_hit'],
        );
    }

    /**
     * @param array<TestIndexType, positive-int> $hit
     * @param array<int, int>                    $out
     * @param array<int, int>                    $out_hit
     */
    public function __construct(
        int $op_start,
        int $op_end,
        int $line_start,
        int $line_end,
        array $hit,
        array $out,
        array $out_hit,
    ) {
        $this->out_hit    = $out_hit;
        $this->out        = $out;
        $this->hit        = $hit;
        $this->line_end   = $line_end;
        $this->line_start = $line_start;
        $this->op_end     = $op_end;
        $this->op_start   = $op_start;
    }

    /**
     * Hit counts for a test case that occurs in both operands are combined with max(), not summed,
     * because the same test case id on both sides means the same test execution was observed twice.
     *
     * @see ProcessedCodeCoverageData::merge()
     */
    #[NoDiscard]
    public function merge(self $data): self
    {
        if ($data->hit === []) {
            return $this;
        }

        $hit = $this->hit;

        foreach ($data->hit as $testIndex => $count) {
            $hit[$testIndex] = max($hit[$testIndex] ?? 0, $count);
        }

        return new self(
            $this->op_start,
            $this->op_end,
            $this->line_start,
            $this->line_end,
            $hit,
            $this->out,
            $this->out_hit,
        );
    }

    /**
     * @param array<TestIndexType, TestIndexType> $remap
     */
    #[NoDiscard]
    public function withRemappedTestIndexes(array $remap): self
    {
        if ($this->hit === []) {
            return $this;
        }

        $hit = [];

        foreach ($this->hit as $testIndex => $count) {
            $hit[$remap[$testIndex] ?? $testIndex] = $count;
        }

        return new self(
            $this->op_start,
            $this->op_end,
            $this->line_start,
            $this->line_end,
            $hit,
            $this->out,
            $this->out_hit,
        );
    }

    /**
     * @param TestIndexType $testIndex
     * @param positive-int  $count
     */
    public function recordHit(int $testIndex, int $count): void
    {
        $this->hit[$testIndex] = ($this->hit[$testIndex] ?? 0) + $count;
    }
}
