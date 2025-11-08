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

use SebastianBergmann\CodeCoverage\Driver\XdebugDriver;

/**
 * @phpstan-import-type TestIdType from ProcessedCodeCoverageData
 * @phpstan-import-type XdebugFunctionCoverageType from XdebugDriver
 */
final class ProcessedFunctionCoverageData {
    public function __construct(
        /** @var array<int, ProcessedBranchCoverageData> */
        public array $branches,
        /** @var array<int, ProcessedPathCoverageData> */
        public array $paths,

    ) {}

    /**
     * @param XdebugFunctionCoverageType $xdebugCoverageData
     */
    static public function fromXdebugCoverage(array $xdebugCoverageData): self
    {
        $branches = [];
        foreach($xdebugCoverageData['branches'] as $branchId => $branch) {
            $branches[$branchId] = ProcessedBranchCoverageData::fromXdebugCoverage($branch);
        }
        $paths = [];
        foreach($xdebugCoverageData['paths'] as $pathId => $path) {
            $paths[$pathId] = ProcessedPathCoverageData::fromXdebugCoverage($path);
        }

        return new self(
            $branches,
            $paths
        );
    }

    public function merge(self $data): self
    {
        $branches = $this->branches;
        foreach($data->branches as $branchId => $branch) {
            if (isset($branches[$branchId])) {
                continue;
            }
            $branches[$branchId] = $branches[$branchId]->merge($branch);
        }

        $paths = $this->paths;
        foreach($data->paths as $pathId => $path) {
            if (isset($paths[$pathId])) {
                continue;
            }
            $paths[$pathId] = $paths[$pathId]->merge($path);
        }

        return new self(
            $branches,
            $paths
        );
    }

    /**
     * @param TestIdType $testCaseId
     */
    public function recordBranchHit(int $branchId, string $testCaseId): void {
        $this->branches[$branchId]->recordHit($testCaseId);
    }

    public function recordPathHit(int $pathId, string $testCaseId): void {
        $this->paths[$pathId]->recordHit($testCaseId);
    }
}
