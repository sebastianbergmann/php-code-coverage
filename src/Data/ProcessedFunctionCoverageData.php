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

use function array_map;
use NoDiscard;
use SebastianBergmann\CodeCoverage\Driver\XdebugDriver;

/**
 * @internal This class is not covered by the backward compatibility promise for phpunit/php-code-coverage
 *
 * @no-named-arguments Parameter names are not covered by the backward compatibility promise for phpunit/php-code-coverage
 *
 * @phpstan-import-type TestIdType from ProcessedCodeCoverageData
 * @phpstan-import-type XdebugFunctionCoverageType from XdebugDriver
 * @phpstan-import-type XdebugBranchCoverageType from XdebugDriver
 * @phpstan-import-type XdebugPathCoverageType from XdebugDriver
 */
final readonly class ProcessedFunctionCoverageData
{
    /** @var array<int, ProcessedBranchCoverageData> */
    public array $branches;

    /** @var array<int, ProcessedPathCoverageData> */
    public array $paths;

    /**
     * @param XdebugFunctionCoverageType $xdebugCoverageData
     */
    public static function fromXdebugCoverage(array $xdebugCoverageData): self
    {
        $branches = array_map(
            /** @param XdebugBranchCoverageType $branch */
            static function (array $branch): ProcessedBranchCoverageData
            {
                return ProcessedBranchCoverageData::fromXdebugCoverage($branch);
            },
            $xdebugCoverageData['branches'],
        );

        $paths = array_map(
            /** @param XdebugPathCoverageType $path */
            static function (array $path): ProcessedPathCoverageData
            {
                return ProcessedPathCoverageData::fromXdebugCoverage($path);
            },
            $xdebugCoverageData['paths'],
        );

        return new self(
            $branches,
            $paths,
        );
    }

    /**
     * @param array<int, ProcessedBranchCoverageData> $branches
     * @param array<int, ProcessedPathCoverageData>   $paths
     */
    public function __construct(
        array $branches,
        array $paths,
    ) {
        $this->paths    = $paths;
        $this->branches = $branches;
    }

    #[NoDiscard]
    public function merge(self $data): self
    {
        $branches = null;

        if ($data->branches !== $this->branches) {
            $branches = $this->branches;

            foreach ($data->branches as $branchId => $branch) {
                if (!isset($branches[$branchId])) {
                    $branches[$branchId] = $branch;
                } else {
                    $branches[$branchId] = $branches[$branchId]->merge($branch);
                }
            }
        }

        $paths = null;

        if ($data->paths !== $this->paths) {
            $paths = $this->paths;

            foreach ($data->paths as $pathId => $path) {
                if (!isset($paths[$pathId])) {
                    $paths[$pathId] = $path;
                } else {
                    $paths[$pathId] = $paths[$pathId]->merge($path);
                }
            }
        }

        if ($branches === null && $paths === null) {
            return $this;
        }

        return new self(
            $branches ?? $this->branches,
            $paths ?? $this->paths,
        );
    }

    /**
     * @param TestIdType $testCaseId
     */
    public function recordBranchHit(int $branchId, string $testCaseId): void
    {
        if (!isset($this->branches[$branchId])) {
            return;
        }

        $this->branches[$branchId]->recordHit($testCaseId);
    }

    /**
     * @param TestIdType $testCaseId
     */
    public function recordPathHit(int $pathId, string $testCaseId): void
    {
        if (!isset($this->paths[$pathId])) {
            return;
        }

        $this->paths[$pathId]->recordHit($testCaseId);
    }
}
