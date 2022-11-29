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

use function array_diff;
use function array_diff_key;
use function array_flip;
use function array_intersect;
use function array_intersect_key;
use function count;
use function explode;
use function file_get_contents;
use function in_array;
use function is_file;
use function range;
use function trim;
use SebastianBergmann\CodeCoverage\Driver\Driver;
use SebastianBergmann\CodeCoverage\StaticAnalysis\FileAnalyser;

/**
 * @internal This class is not covered by the backward compatibility promise for phpunit/php-code-coverage
 */
final class RawCodeCoverageData
{
    /**
     * @var array<string, array<int>>
     */
    private static $emptyLineCache = [];

    /**
     * @var array
     *
     * @see https://xdebug.org/docs/code_coverage for format
     */
    private $lineCoverage;

    /**
     * @var array
     *
     * @see https://xdebug.org/docs/code_coverage for format
     */
    private $functionCoverage;

    public static function fromXdebugWithoutPathCoverage(array $rawCoverage): self
    {
        return new self($rawCoverage, []);
    }

    public static function fromXdebugWithPathCoverage(array $rawCoverage): self
    {
        $lineCoverage     = [];
        $functionCoverage = [];

        foreach ($rawCoverage as $file => $fileCoverageData) {
            $lineCoverage[$file]     = $fileCoverageData['lines'];
            $functionCoverage[$file] = $fileCoverageData['functions'];
        }

        return new self($lineCoverage, $functionCoverage);
    }

    public static function fromXdebugWithMixedCoverage(array $rawCoverage): self
    {
        $lineCoverage     = [];
        $functionCoverage = [];

        foreach ($rawCoverage as $file => $fileCoverageData) {
            if (!isset($fileCoverageData['functions'])) {
                // Current file does not have functions, so line coverage
                // is stored in $fileCoverageData, not in $fileCoverageData['lines']
                $lineCoverage[$file] = $fileCoverageData;

                continue;
            }

            $lineCoverage[$file]     = $fileCoverageData['lines'];
            $functionCoverage[$file] = $fileCoverageData['functions'];
        }

        return new self($lineCoverage, $functionCoverage);
    }

    public static function fromUncoveredFile(string $filename, FileAnalyser $analyser): self
    {
        $lineCoverage = [];

        foreach ($analyser->executableLinesIn($filename) as $line => $branch) {
            $lineCoverage[$line] = Driver::LINE_NOT_EXECUTED;
        }

        return new self([$filename => $lineCoverage], []);
    }

    private function __construct(array $lineCoverage, array $functionCoverage)
    {
        $this->lineCoverage     = $lineCoverage;
        $this->functionCoverage = $functionCoverage;

        $this->skipEmptyLines();
    }

    public function clear(): void
    {
        $this->lineCoverage = $this->functionCoverage = [];
    }

    public function lineCoverage(): array
    {
        return $this->lineCoverage;
    }

    public function functionCoverage(): array
    {
        return $this->functionCoverage;
    }

    public function removeCoverageDataForFile(string $filename): void
    {
        unset($this->lineCoverage[$filename], $this->functionCoverage[$filename]);
    }

    /**
     * @param int[] $lines
     */
    public function keepLineCoverageDataOnlyForLines(string $filename, array $lines): void
    {
        if (!isset($this->lineCoverage[$filename])) {
            return;
        }

        $this->lineCoverage[$filename] = array_intersect_key(
            $this->lineCoverage[$filename],
            array_flip($lines)
        );
    }

    /**
     * @param int[] $linesToBranchMap
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

            if (Driver::LINE_EXECUTED === $lineStatus) {
                unset($linesByBranch[$branch]);
            }
        }
    }

    /**
     * @param int[] $lines
     */
    public function keepFunctionCoverageDataOnlyForLines(string $filename, array $lines): void
    {
        if (!isset($this->functionCoverage[$filename])) {
            return;
        }

        foreach ($this->functionCoverage[$filename] as $functionName => $functionData) {
            foreach ($functionData['branches'] as $branchId => $branch) {
                if (count(array_diff(range($branch['line_start'], $branch['line_end']), $lines)) > 0) {
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
     * @param int[] $lines
     */
    public function removeCoverageDataForLines(string $filename, array $lines): void
    {
        if (empty($lines)) {
            return;
        }

        if (!isset($this->lineCoverage[$filename])) {
            return;
        }

        $this->lineCoverage[$filename] = array_diff_key(
            $this->lineCoverage[$filename],
            array_flip($lines)
        );

        if (isset($this->functionCoverage[$filename])) {
            foreach ($this->functionCoverage[$filename] as $functionName => $functionData) {
                foreach ($functionData['branches'] as $branchId => $branch) {
                    if (count(array_intersect($lines, range($branch['line_start'], $branch['line_end']))) > 0) {
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
    }

    /**
     * At the end of a file, the PHP interpreter always sees an implicit return. Where this occurs in a file that has
     * e.g. a class definition, that line cannot be invoked from a test and results in confusing coverage. This engine
     * implementation detail therefore needs to be masked which is done here by simply ensuring that all empty lines
     * are skipped over for coverage purposes.
     *
     * @see https://github.com/sebastianbergmann/php-code-coverage/issues/799
     */
    private function skipEmptyLines(): void
    {
        foreach ($this->lineCoverage as $filename => $coverage) {
            foreach ($this->getEmptyLinesForFile($filename) as $emptyLine) {
                unset($this->lineCoverage[$filename][$emptyLine]);
            }
        }
    }

    private function getEmptyLinesForFile(string $filename): array
    {
        if (!isset(self::$emptyLineCache[$filename])) {
            self::$emptyLineCache[$filename] = [];

            if (is_file($filename)) {
                $sourceLines = explode("\n", file_get_contents($filename));

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
