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

use function array_merge;
use function array_unique;
use SebastianBergmann\CodeCoverage\Driver\XdebugDriver;

/**
 * @phpstan-import-type TestIdType from ProcessedCodeCoverageData
 * @phpstan-import-type XdebugBranchCoverageType from XdebugDriver
 */
final readonly class ProcessedBranchCoverageData
{
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

    public function __construct(
        public int $op_start,
        public int $op_end,
        public int $line_start,
        public int $line_end,
        /** @var list<TestIdType> */
        public array $hit,
        /** @var array<int, int> */
        public array $out,
        /** @var array<int, int> */
        public array $out_hit,
    ) {
    }

    public function merge(self $data): self
    {
        return new self(
            $this->op_start,
            $this->op_end,
            $this->line_start,
            $this->line_end,
            array_unique(array_merge($this->hit, $data->hit)),
            $this->out,
            $this->out_hit,
        );
    }

    /**
     * @param TestIdType $testCaseId
     */
    public function recordHit(string $testCaseId): self
    {
        $hit   = $this->hit;
        $hit[] = $testCaseId;

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
}
