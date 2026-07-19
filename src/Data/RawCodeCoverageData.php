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

use function array_diff_key;
use function array_intersect_key;
use function array_map;
use function explode;
use function file_get_contents;
use function in_array;
use function is_file;
use function preg_replace;
use function str_ends_with;
use function str_starts_with;
use function trim;
use SebastianBergmann\CodeCoverage\Driver\Driver;
use SebastianBergmann\CodeCoverage\StaticAnalysis\FileAnalyser;

/**
 * The types defined here are the contract for the raw code coverage data
 * that drivers must produce.
 *
 * Line coverage (LinesCoverageType) maps line numbers to Driver::LINE_NOT_EXECUTABLE (-2),
 * Driver::LINE_NOT_EXECUTED (-1), or a value >= Driver::LINE_EXECUTED (1). Drivers whose
 * collectsHitCounts() method returns true report how often a line was executed; other
 * drivers report 1 for "executed at least once".
 *
 * The hit value of a branch or path is Driver::BRANCH_NOT_HIT (0) or a value >=
 * Driver::BRANCH_HIT (1), the number of times the branch or path was traversed. It is an
 * exact traversal count only when collectsHitCounts() returns true.
 *
 * Function coverage (FunctionsCoverageType) is keyed "Namespace\Class->method" for
 * methods, "Namespace\function" for functions, and "{main}" for code that is not part of
 * a function or method.
 *
 * op_start and op_end of a branch are opcode indexes for drivers with opcode granularity;
 * drivers without it fill in 0 for both. Consumers must not assign meaning to these
 * values beyond identity and ordering.
 *
 * out and out_hit of a branch describe the edges to successor branches. They are optional
 * for drivers that do not model a branch graph: empty arrays are valid, reports that
 * render a control flow graph degrade gracefully.
 *
 * line_start <= line_end is an invariant of BranchCoverageType. Xdebug reports loop
 * back-edge branches with line_start > line_end; fromXdebugWithPathCoverage() normalizes
 * them, so consumers do not have to.
 *
 * The stripping of the trait method suffix that Xdebug appends to function keys
 * ("Foo->bar{trait-method:...}") happens in fromXdebugWithPathCoverage() and is not part
 * of this contract: data passed to fromLineAndBranchCoverage() must already use canonical
 * function keys.
 *
 * @internal This class is not covered by the backward compatibility promise for phpunit/php-code-coverage
 *
 * @no-named-arguments Parameter names are not covered by the backward compatibility promise for phpunit/php-code-coverage
 *
 * @phpstan-type LinesCoverageType array<positive-int, int>
 * @phpstan-type BranchCoverageType array{
 *     op_start: int,
 *     op_end: int,
 *     line_start: int,
 *     line_end: int,
 *     hit: int,
 *     out: array<int, int>,
 *     out_hit: array<int, int>,
 * }
 * @phpstan-type PathCoverageType array{
 *     path: array<int, int>,
 *     hit: int,
 * }
 * @phpstan-type FunctionCoverageType array{
 *     branches: array<int, BranchCoverageType>,
 *     paths: array<int, PathCoverageType>,
 * }
 * @phpstan-type FunctionsCoverageType array<non-empty-string, FunctionCoverageType>
 * @phpstan-type PathAndBranchesCoverageType array{
 *     lines: LinesCoverageType,
 *     functions: FunctionsCoverageType,
 * }
 * @phpstan-type CodeCoverageWithoutPathCoverageType array<non-empty-string, LinesCoverageType>
 * @phpstan-type CodeCoverageWithPathCoverageType array<non-empty-string, PathAndBranchesCoverageType>
 */
final class RawCodeCoverageData
{
    /**
     * @var array<string, array<int>>
     */
    private static array $emptyLineCache = [];

    /**
     * @var CodeCoverageWithoutPathCoverageType
     */
    private array $lineCoverage;

    /**
     * @var array<non-empty-string, FunctionsCoverageType>
     */
    private array $functionCoverage;

    /**
     * @param CodeCoverageWithoutPathCoverageType $rawCoverage
     */
    public static function fromXdebugWithoutPathCoverage(array $rawCoverage): self
    {
        return new self($rawCoverage, []);
    }

    /**
     * @param CodeCoverageWithPathCoverageType $rawCoverage
     */
    public static function fromXdebugWithPathCoverage(array $rawCoverage): self
    {
        $lineCoverage     = [];
        $functionCoverage = [];

        foreach ($rawCoverage as $file => $fileCoverageData) {
            // Xdebug annotates the function name of traits, strip that off
            foreach ($fileCoverageData['functions'] as $existingKey => $data) {
                if (str_ends_with($existingKey, '}') && !str_starts_with($existingKey, '{')) { // don't want to catch {main}
                    $newKey = preg_replace('/\{.*}$/', '', $existingKey);

                    if ($newKey === null) {
                        continue;
                    }

                    $fileCoverageData['functions'][$newKey] = $data;

                    unset($fileCoverageData['functions'][$existingKey]);
                }
            }

            // Xdebug reports loop back-edge branches with line_start > line_end
            foreach ($fileCoverageData['functions'] as $functionKey => $functionData) {
                foreach ($functionData['branches'] as $branchId => $branch) {
                    if ($branch['line_start'] > $branch['line_end']) {
                        $fileCoverageData['functions'][$functionKey]['branches'][$branchId]['line_start'] = $branch['line_end'];
                        $fileCoverageData['functions'][$functionKey]['branches'][$branchId]['line_end']   = $branch['line_start'];
                    }
                }
            }

            $lineCoverage[$file]     = $fileCoverageData['lines'];
            $functionCoverage[$file] = $fileCoverageData['functions'];
        }

        return new self($lineCoverage, $functionCoverage);
    }

    /**
     * @param CodeCoverageWithoutPathCoverageType            $lineCoverage
     * @param array<non-empty-string, FunctionsCoverageType> $functionCoverage
     */
    public static function fromLineAndBranchCoverage(array $lineCoverage, array $functionCoverage): self
    {
        return new self($lineCoverage, $functionCoverage);
    }

    /**
     * @param non-empty-string $filename
     */
    public static function fromUncoveredFile(string $filename, FileAnalyser $analyser): self
    {
        $analysisResult = $analyser->analyse($filename);

        $lineCoverage = array_map(
            static fn (): int => Driver::LINE_NOT_EXECUTED,
            $analysisResult->executableLines(),
        );

        foreach ($analysisResult->deadLines() as $line => $_) {
            $lineCoverage[$line] = Driver::LINE_NOT_EXECUTABLE;
        }

        return new self([$filename => $lineCoverage], []);
    }

    /**
     * @param CodeCoverageWithoutPathCoverageType            $lineCoverage
     * @param array<non-empty-string, FunctionsCoverageType> $functionCoverage
     */
    private function __construct(array $lineCoverage, array $functionCoverage)
    {
        $this->lineCoverage     = $lineCoverage;
        $this->functionCoverage = $functionCoverage;
    }

    public function clear(): void
    {
        $this->lineCoverage = $this->functionCoverage = [];
    }

    /**
     * @return CodeCoverageWithoutPathCoverageType
     */
    public function lineCoverage(): array
    {
        return $this->lineCoverage;
    }

    /**
     * @return array<non-empty-string, FunctionsCoverageType>
     */
    public function functionCoverage(): array
    {
        return $this->functionCoverage;
    }

    public function removeCoverageDataForFile(string $filename): void
    {
        unset($this->lineCoverage[$filename], $this->functionCoverage[$filename]);
    }

    /**
     * @param array<positive-int, mixed> $lines keyed by line number
     */
    public function keepLineCoverageDataOnlyForLines(string $filename, array $lines): void
    {
        if (!isset($this->lineCoverage[$filename])) {
            return;
        }

        $this->lineCoverage[$filename] = array_intersect_key(
            $this->lineCoverage[$filename],
            $lines,
        );
    }

    /**
     * @param non-empty-string           $filename
     * @param array<positive-int, mixed> $lines    keyed by line number
     */
    public function addMissingExecutableLines(string $filename, array $lines): void
    {
        if (!isset($this->lineCoverage[$filename])) {
            return;
        }

        foreach ($lines as $line => $_) {
            if (!isset($this->lineCoverage[$filename][$line])) {
                $this->lineCoverage[$filename][$line] = Driver::LINE_NOT_EXECUTED;
            }
        }
    }

    /**
     * @param non-empty-string          $filename
     * @param array<positive-int, true> $lines
     */
    public function markLinesAsNotExecutable(string $filename, array $lines): void
    {
        if (!isset($this->lineCoverage[$filename])) {
            return;
        }

        foreach ($lines as $line => $_) {
            $this->lineCoverage[$filename][$line] = Driver::LINE_NOT_EXECUTABLE;
        }
    }

    /**
     * @param non-empty-string         $filename
     * @param array<positive-int, int> $linesToBranchMap
     */
    public function markExecutableLineByBranch(string $filename, array $linesToBranchMap): void
    {
        if (!isset($this->lineCoverage[$filename])) {
            return;
        }

        $linesByBranch = [];

        foreach ($linesToBranchMap as $line => $branch) {
            $linesByBranch[$branch][] = $line;
        }

        foreach ($this->lineCoverage[$filename] as $line => $lineStatus) {
            if (!isset($linesToBranchMap[$line])) {
                continue;
            }

            $branch = $linesToBranchMap[$line];

            if (!isset($linesByBranch[$branch])) {
                continue;
            }

            foreach ($linesByBranch[$branch] as $lineInBranch) {
                $this->lineCoverage[$filename][$lineInBranch] = $lineStatus;
            }

            if ($lineStatus >= Driver::LINE_EXECUTED) {
                unset($linesByBranch[$branch]);
            }
        }
    }

    /**
     * @param array<positive-int, mixed> $lines keyed by line number
     */
    public function keepFunctionCoverageDataOnlyForLines(string $filename, array $lines): void
    {
        if (!isset($this->functionCoverage[$filename])) {
            return;
        }

        foreach ($this->functionCoverage[$filename] as $functionName => $functionData) {
            foreach ($functionData['branches'] as $branchId => $branch) {
                $allBranchLinesIncluded = true;

                for ($line = $branch['line_start']; $line <= $branch['line_end']; $line++) {
                    if (!isset($lines[$line])) {
                        $allBranchLinesIncluded = false;

                        break;
                    }
                }

                if ($allBranchLinesIncluded) {
                    continue;
                }

                unset($this->functionCoverage[$filename][$functionName]['branches'][$branchId]);

                foreach ($functionData['paths'] as $pathId => $path) {
                    if (in_array($branchId, $path['path'], true)) {
                        unset($this->functionCoverage[$filename][$functionName]['paths'][$pathId]);
                    }
                }
            }
        }
    }

    /**
     * @param array<int, mixed> $lines keyed by line number
     */
    public function removeCoverageDataForLines(string $filename, array $lines): void
    {
        if ($lines === []) {
            return;
        }

        if (!isset($this->lineCoverage[$filename])) {
            return;
        }

        $this->lineCoverage[$filename] = array_diff_key(
            $this->lineCoverage[$filename],
            $lines,
        );

        if (isset($this->functionCoverage[$filename])) {
            foreach ($this->functionCoverage[$filename] as $functionName => $functionData) {
                foreach ($functionData['branches'] as $branchId => $branch) {
                    $branchTouchesRemovedLine = false;

                    for ($line = $branch['line_start']; $line <= $branch['line_end']; $line++) {
                        if (isset($lines[$line])) {
                            $branchTouchesRemovedLine = true;

                            break;
                        }
                    }

                    if (!$branchTouchesRemovedLine) {
                        continue;
                    }

                    unset($this->functionCoverage[$filename][$functionName]['branches'][$branchId]);

                    foreach ($functionData['paths'] as $pathId => $path) {
                        if (in_array($branchId, $path['path'], true)) {
                            unset($this->functionCoverage[$filename][$functionName]['paths'][$pathId]);
                        }
                    }
                }
            }
        }
    }

    /**
     * At the end of a file, the PHP interpreter always sees an implicit return. Where this occurs in a file that has
     * e.g. a class definition, that line cannot be invoked from a test and results in confusing coverage. This engine
     * implementation detail therefore needs to be masked which is done here by simply ensuring that all empty lines
     * are skipped over for coverage purposes.
     *
     * @see https://github.com/sebastianbergmann/php-code-coverage/issues/799
     */
    public function skipEmptyLines(): void
    {
        foreach ($this->lineCoverage as $filename => $coverage) {
            foreach ($this->getEmptyLinesForFile($filename) as $emptyLine) {
                unset($this->lineCoverage[$filename][$emptyLine]);
            }
        }
    }

    /**
     * @return array<int>
     */
    private function getEmptyLinesForFile(string $filename): array
    {
        if (!isset(self::$emptyLineCache[$filename])) {
            self::$emptyLineCache[$filename] = [];

            $sourceCode = is_file($filename) ? file_get_contents($filename) : false;

            if ($sourceCode !== false) {
                $sourceLines = explode("\n", $sourceCode);

                foreach ($sourceLines as $line => $source) {
                    if (trim($source) === '') {
                        self::$emptyLineCache[$filename][] = ($line + 1);
                    }
                }
            }
        }

        return self::$emptyLineCache[$filename];
    }
}
