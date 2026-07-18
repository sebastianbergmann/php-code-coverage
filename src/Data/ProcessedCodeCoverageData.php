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

use function array_flip;
use function array_key_exists;
use function array_keys;
use function count;
use function is_array;
use function ksort;
use function max;
use SebastianBergmann\CodeCoverage\Driver\Driver;
use SebastianBergmann\CodeCoverage\Driver\XdebugDriver;

/**
 * @internal This class is not covered by the backward compatibility promise for phpunit/php-code-coverage
 *
 * @no-named-arguments Parameter names are not covered by the backward compatibility promise for phpunit/php-code-coverage
 *
 * @phpstan-import-type XdebugFunctionCoverageType from XdebugDriver
 *
 * @phpstan-type TestIdType non-empty-string
 * @phpstan-type TestIndexType non-negative-int
 * @phpstan-type FunctionCoverageType array<non-empty-string, array<non-empty-string, ProcessedFunctionCoverageData>>
 * @phpstan-type LineCoverageType array<non-empty-string, array<positive-int, null|array<TestIndexType, positive-int>>>
 */
final class ProcessedCodeCoverageData
{
    /**
     * Line coverage data.
     * An array of filenames, each having an array of linenumbers, each executable line having a map of
     * test index to the number of times the testcase executed the line (1 for drivers that do not
     * collect hit counts).
     *
     * @var LineCoverageType
     */
    private array $lineCoverage = [];

    /**
     * Test case ids are interned: the first time a test case records coverage it is assigned the
     * next free index, and all per-line, per-branch, and per-path hit maps are keyed by that index
     * instead of the id string. This keeps the hit maps small and makes inserting and merging
     * cheap. Use testIds() to resolve an index back to the test case id.
     *
     * @var array<TestIdType, TestIndexType>
     */
    private array $testIdToIndex = [];

    /**
     * Function coverage data.
     * Maintains base format of raw data (@see https://xdebug.org/docs/code_coverage), but each 'hit' entry is a map
     * of test index to the number of times the testcase traversed the branch or path (1 for drivers that do not
     * collect hit counts).
     *
     * @var FunctionCoverageType
     */
    private array $functionCoverage = [];

    /**
     * Whether the hit counts in this object are exact execution counts (the driver that collected
     * the data counts how often a line was executed) or only mean "executed at least once".
     */
    private bool $collectsHitCounts;
    private bool $lineCoverageSorted     = true;
    private bool $functionCoverageSorted = true;

    public function __construct(bool $collectsHitCounts = false)
    {
        $this->collectsHitCounts = $collectsHitCounts;
    }

    public function collectsHitCounts(): bool
    {
        return $this->collectsHitCounts;
    }

    public function initializeUnseenData(RawCodeCoverageData $rawData): void
    {
        foreach ($rawData->lineCoverage() as $file => $lines) {
            if (!isset($this->lineCoverage[$file])) {
                $this->lineCoverage[$file] = [];
                $this->lineCoverageSorted  = false;

                foreach ($lines as $k => $v) {
                    $this->lineCoverage[$file][$k] = $v === Driver::LINE_NOT_EXECUTABLE ? null : [];
                }
            }
        }

        foreach ($rawData->functionCoverage() as $file => $functions) {
            foreach ($functions as $functionName => $functionData) {
                if (isset($this->functionCoverage[$file][$functionName])) {
                    $this->initPreviouslySeenFunction($file, $functionName, $functionData);
                } else {
                    $this->initPreviouslyUnseenFunction($file, $functionName, $functionData);
                }
            }
        }
    }

    /**
     * @param non-empty-string $testCaseId
     */
    public function markCodeAsExecutedByTestCase(string $testCaseId, RawCodeCoverageData $executedCode): void
    {
        $testIndex = $this->testIndex($testCaseId);

        foreach ($executedCode->lineCoverage() as $file => $lines) {
            if (!isset($this->lineCoverage[$file])) {
                $this->lineCoverage[$file] = [];
                $this->lineCoverageSorted  = false;
            }

            $fileCoverage = &$this->lineCoverage[$file];

            foreach ($lines as $k => $v) {
                if ($v >= Driver::LINE_EXECUTED) {
                    $fileCoverage[$k][$testIndex] = ($fileCoverage[$k][$testIndex] ?? 0) + $v;
                }
            }

            unset($fileCoverage);
        }

        foreach ($executedCode->functionCoverage() as $file => $functions) {
            foreach ($functions as $functionName => $functionData) {
                if (!isset($this->functionCoverage[$file][$functionName])) {
                    continue;
                }

                $functionCoverage = $this->functionCoverage[$file][$functionName];

                foreach ($functionData['branches'] as $branchId => $branchData) {
                    if ($branchData['hit'] >= Driver::BRANCH_HIT) {
                        $functionCoverage->recordBranchHit($branchId, $testIndex, $branchData['hit']);
                    }
                }

                foreach ($functionData['paths'] as $pathId => $pathData) {
                    if ($pathData['hit'] >= Driver::BRANCH_HIT) {
                        $functionCoverage->recordPathHit($pathId, $testIndex, $pathData['hit']);
                    }
                }
            }
        }
    }

    /**
     * @param LineCoverageType $lineCoverage
     */
    public function setLineCoverage(array $lineCoverage): void
    {
        $this->lineCoverage       = $lineCoverage;
        $this->lineCoverageSorted = false;
    }

    /**
     * @return LineCoverageType
     */
    public function lineCoverage(): array
    {
        $this->sortLineCoverage();

        return $this->lineCoverage;
    }

    /**
     * @param FunctionCoverageType $functionCoverage
     */
    public function setFunctionCoverage(array $functionCoverage): void
    {
        $this->functionCoverage       = $functionCoverage;
        $this->functionCoverageSorted = false;
    }

    /**
     * @return FunctionCoverageType
     */
    public function functionCoverage(): array
    {
        if (!$this->functionCoverageSorted) {
            ksort($this->functionCoverage);

            $this->functionCoverageSorted = true;
        }

        return $this->functionCoverage;
    }

    /**
     * @return list<non-empty-string>
     */
    public function coveredFiles(): array
    {
        $this->sortLineCoverage();

        return array_keys($this->lineCoverage);
    }

    /**
     * @param array<TestIndexType, TestIdType> $testIds
     */
    public function setTestIds(array $testIds): void
    {
        $this->testIdToIndex = array_flip($testIds);
    }

    /**
     * @return array<TestIndexType, TestIdType>
     */
    public function testIds(): array
    {
        return array_flip($this->testIdToIndex);
    }

    /**
     * @param non-empty-string $oldFile
     * @param non-empty-string $newFile
     */
    public function renameFile(string $oldFile, string $newFile): void
    {
        if (isset($this->lineCoverage[$oldFile])) {
            $this->lineCoverage[$newFile] = $this->lineCoverage[$oldFile];
            $this->lineCoverageSorted     = false;
        }

        if (isset($this->functionCoverage[$oldFile])) {
            $this->functionCoverage[$newFile] = $this->functionCoverage[$oldFile];
            $this->functionCoverageSorted     = false;
        }

        unset($this->lineCoverage[$oldFile], $this->functionCoverage[$oldFile]);
    }

    /**
     * Hit counts for a test case that occurs in both operands are combined with max(), not summed:
     * the same test case id on both sides means the same test execution was observed twice (for
     * example when coverage data collected in parallel is merged), not that the test ran twice.
     * This preserves the deduplication semantics of the list-based representation that was used
     * before hit counts were recorded. Accumulation of hit counts within a single test run
     * happens in markCodeAsExecutedByTestCase() and recordHit() instead.
     *
     * The merged data only contains exact hit counts if both operands do.
     */
    public function merge(self $newData): void
    {
        $this->collectsHitCounts = $this->collectsHitCounts && $newData->collectsHitCounts;

        [$newLineCoverage, $newFunctionCoverage] = $this->withTestIndexesRemappedToThisObject($newData);

        foreach ($newLineCoverage as $file => $lines) {
            if (!isset($this->lineCoverage[$file])) {
                $this->lineCoverage[$file] = $lines;
                $this->lineCoverageSorted  = false;

                continue;
            }

            $fileCoverage = &$this->lineCoverage[$file];

            foreach ($lines as $line => $data) {
                $thatPriority = $this->priorityForValue($data);
                $thisPriority = $this->priorityForLine($fileCoverage, $line);

                if ($thatPriority > $thisPriority) {
                    $fileCoverage[$line] = $data;
                } elseif ($thatPriority === $thisPriority &&
                    is_array($data) &&
                    array_key_exists($line, $fileCoverage) &&
                    is_array($fileCoverage[$line])) {
                    foreach ($data as $testIndex => $hits) {
                        $fileCoverage[$line][$testIndex] = max(
                            $fileCoverage[$line][$testIndex] ?? 0,
                            $hits,
                        );
                    }
                }
            }

            unset($fileCoverage);
        }

        foreach ($newFunctionCoverage as $file => $functions) {
            if (!isset($this->functionCoverage[$file])) {
                $this->functionCoverage[$file] = $functions;
                $this->functionCoverageSorted  = false;

                continue;
            }

            foreach ($functions as $functionName => $functionData) {
                if (isset($this->functionCoverage[$file][$functionName])) {
                    $this->initPreviouslySeenFunction($file, $functionName, $functionData);
                } else {
                    $this->initPreviouslyUnseenFunction($file, $functionName, $functionData);
                }
            }
        }
    }

    private function sortLineCoverage(): void
    {
        if (!$this->lineCoverageSorted) {
            ksort($this->lineCoverage);

            $this->lineCoverageSorted = true;
        }
    }

    /**
     * @param TestIdType $testCaseId
     *
     * @return TestIndexType
     */
    private function testIndex(string $testCaseId): int
    {
        if (!isset($this->testIdToIndex[$testCaseId])) {
            $this->testIdToIndex[$testCaseId] = count($this->testIdToIndex);
        }

        return $this->testIdToIndex[$testCaseId];
    }

    /**
     * The test indexes of another ProcessedCodeCoverageData object are meaningless in the context
     * of this object: the same index may refer to a different test case id. This method interns
     * the other object's test case ids into this object's index table and returns the other
     * object's line and function coverage with all test indexes translated accordingly. When both
     * objects agree on the numbering the data is returned as-is.
     *
     * @return array{0: LineCoverageType, 1: FunctionCoverageType}
     */
    private function withTestIndexesRemappedToThisObject(self $newData): array
    {
        $remap           = [];
        $remapIsIdentity = true;

        foreach ($newData->testIdToIndex as $testId => $index) {
            $remap[$index] = $this->testIndex($testId);

            if ($remap[$index] !== $index) {
                $remapIsIdentity = false;
            }
        }

        if ($remapIsIdentity) {
            return [$newData->lineCoverage, $newData->functionCoverage];
        }

        $lineCoverage = $newData->lineCoverage;

        foreach ($lineCoverage as $file => $lines) {
            foreach ($lines as $line => $data) {
                if ($data === null || $data === []) {
                    continue;
                }

                $remapped = [];

                foreach ($data as $index => $hits) {
                    $remapped[$remap[$index] ?? $index] = $hits;
                }

                $lineCoverage[$file][$line] = $remapped;
            }
        }

        $functionCoverage = $newData->functionCoverage;

        foreach ($functionCoverage as $file => $functions) {
            foreach ($functions as $functionName => $functionData) {
                $functionCoverage[$file][$functionName] = $functionData->withRemappedTestIndexes($remap);
            }
        }

        return [$lineCoverage, $functionCoverage];
    }

    /**
     * Determine the priority for a line.
     *
     * 1 = the line is not set
     * 2 = the line has not been tested
     * 3 = the line is dead code
     * 4 = the line has been tested
     *
     * During a merge, a higher number is better.
     *
     * @param array<positive-int, null|array<TestIndexType, positive-int>> $data
     * @param positive-int                                                 $line
     *
     * @return 1|2|3|4
     */
    private function priorityForLine(array $data, int $line): int
    {
        if (!array_key_exists($line, $data)) {
            return 1;
        }

        return $this->priorityForValue($data[$line]);
    }

    /**
     * @param null|array<TestIndexType, positive-int> $data
     *
     * @return 2|3|4
     */
    private function priorityForValue(null|array $data): int
    {
        if ($data === null) {
            return 3;
        }

        if (count($data) === 0) {
            return 2;
        }

        return 4;
    }

    /**
     * For a function we have never seen before, copy all data over and simply init the 'hit' array.
     *
     * @param non-empty-string                                         $file
     * @param non-empty-string                                         $functionName
     * @param ProcessedFunctionCoverageData|XdebugFunctionCoverageType $functionData
     */
    private function initPreviouslyUnseenFunction(string $file, string $functionName, array|ProcessedFunctionCoverageData $functionData): void
    {
        if (is_array($functionData)) {
            $functionData = ProcessedFunctionCoverageData::fromXdebugCoverage($functionData);
        }

        if (!isset($this->functionCoverage[$file])) {
            $this->functionCoverageSorted = false;
        }

        $this->functionCoverage[$file][$functionName] = $functionData;
    }

    /**
     * For a function we have seen before, only copy over and init the 'hit' array for any unseen branches and paths.
     * Techniques such as mocking and where the contents of a file are different vary during tests (e.g. compiling
     * containers) mean that the functions inside a file cannot be relied upon to be static.
     *
     * @param non-empty-string                                         $file
     * @param non-empty-string                                         $functionName
     * @param ProcessedFunctionCoverageData|XdebugFunctionCoverageType $functionData
     */
    private function initPreviouslySeenFunction(string $file, string $functionName, array|ProcessedFunctionCoverageData $functionData): void
    {
        if (is_array($functionData)) {
            $functionData = ProcessedFunctionCoverageData::fromXdebugCoverage($functionData);
        }

        $existing = $this->functionCoverage[$file][$functionName] ?? null;

        if ($existing !== null) {
            $this->functionCoverage[$file][$functionName] = $existing->merge($functionData);
        }
    }
}
