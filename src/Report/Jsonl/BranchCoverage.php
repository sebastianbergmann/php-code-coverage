<?php declare(strict_types=1);
/*
 * This file is part of phpunit/php-code-coverage.
 *
 * (c) Sebastian Bergmann <sebastian@phpunit.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace SebastianBergmann\CodeCoverage\Report\Jsonl;

use function count;
use SebastianBergmann\CodeCoverage\Data\ProcessedBranchCoverageData;

/**
 * @internal This class is not covered by the backward compatibility promise for phpunit/php-code-coverage
 *
 * @no-named-arguments Parameter names are not covered by the backward compatibility promise for phpunit/php-code-coverage
 */
final readonly class BranchCoverage
{
    public int $executed;
    public int $total;

    /**
     * @var list<int>
     */
    public array $uncoveredLines;

    /**
     * @param array<int, ProcessedBranchCoverageData> $branches
     */
    public static function fromBranches(array $branches): self
    {
        $executed       = 0;
        $uncoveredLines = [];

        foreach ($branches as $branch) {
            if ($branch->hit === []) {
                for ($line = $branch->line_start; $line <= $branch->line_end; $line++) {
                    $uncoveredLines[] = $line;
                }

                continue;
            }

            $executed++;

            // A block with a single outgoing edge is not a decision; an edge
            // out of it that was never taken means the block left the function
            // rather than that a branch was missed
            if (count($branch->out) < 2) {
                continue;
            }

            foreach ($branch->out as $edge => $destination) {
                if (($branch->out_hit[$edge] ?? 0) > 0) {
                    continue;
                }

                $uncoveredLines[] = $branch->line_end;

                break;
            }
        }

        return new self($executed, count($branches), $uncoveredLines);
    }

    /**
     * @param list<int> $uncoveredLines
     */
    private function __construct(int $executed, int $total, array $uncoveredLines)
    {
        $this->executed       = $executed;
        $this->total          = $total;
        $this->uncoveredLines = $uncoveredLines;
    }
}
