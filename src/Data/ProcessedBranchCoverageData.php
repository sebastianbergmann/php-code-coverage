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

use NoDiscard;

/**
 * @internal This class is not covered by the backward compatibility promise for phpunit/php-code-coverage
 *
 * @no-named-arguments Parameter names are not covered by the backward compatibility promise for phpunit/php-code-coverage
 *
 * @phpstan-import-type TestIndexType from ProcessedCodeCoverageData
 * @phpstan-import-type BranchCoverageType from RawCodeCoverageData
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

    /**
     * Keyed like $out: 0 = the edge was never taken, > 0 = the edge was taken by a test whose
     * coverage was recorded. This is a flag, not a traversal count.
     *
     * @var array<int, non-negative-int>
     */
    public array $out_hit;

    /**
     * The structure of the branch is taken from the raw data, what was executed is not:
     * neither the hits of the branch nor the edges taken out of it are, because the raw data
     * may be from a run whose coverage is not recorded. Both are recorded by recordHit().
     *
     * @param BranchCoverageType $xdebugCoverageData
     */
    public static function fromXdebugCoverage(array $xdebugCoverageData): self
    {
        $outHit = $xdebugCoverageData['out_hit'];

        foreach ($outHit as $edge => $taken) {
            if ($taken !== 0) {
                $outHit[$edge] = 0;
            }
        }

        return new self(
            $xdebugCoverageData['op_start'],
            $xdebugCoverageData['op_end'],
            $xdebugCoverageData['line_start'],
            $xdebugCoverageData['line_end'],
            [],
            $xdebugCoverageData['out'],
            $outHit,
        );
    }

    /**
     * @param array<TestIndexType, positive-int> $hit
     * @param array<int, int>                    $out
     * @param array<int, non-negative-int>       $out_hit
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
     * An edge is taken when it is taken on either side. A branch without hits has no edges
     * that were taken, which is why such an operand does not contribute anything.
     *
     * @see ProcessedCodeCoverageData::merge()
     */
    #[NoDiscard]
    public function merge(self $data): self
    {
        if ($data->hit === []) {
            return $this;
        }

        $outHit = $this->out_hit;

        foreach ($data->out_hit as $edge => $taken) {
            if (!isset($outHit[$edge]) || $taken <= $outHit[$edge]) {
                continue;
            }

            if (($this->out[$edge] ?? null) !== ($data->out[$edge] ?? null)) {
                continue;
            }

            $outHit[$edge] = $taken;
        }

        return new self(
            $this->op_start,
            $this->op_end,
            $this->line_start,
            $this->line_end,
            HitMap::merge($this->hit, $data->hit),
            $this->out,
            $outHit,
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

        return new self(
            $this->op_start,
            $this->op_end,
            $this->line_start,
            $this->line_end,
            HitMap::withRemappedTestIndexes($this->hit, $remap),
            $this->out,
            $this->out_hit,
        );
    }

    /**
     * @param TestIndexType                $testIndex
     * @param positive-int                 $count
     * @param array<int, non-negative-int> $outHit    the edges out of the branch that the test took, keyed like $out
     */
    public function recordHit(int $testIndex, int $count, array $outHit): void
    {
        $this->hit[$testIndex] = ($this->hit[$testIndex] ?? 0) + $count;

        foreach ($outHit as $edge => $taken) {
            if (isset($this->out_hit[$edge]) && $taken > $this->out_hit[$edge]) {
                $this->out_hit[$edge] = $taken;
            }
        }
    }
}
