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
use function array_key_exists;
use function array_keys;
use function is_file;
use function usort;
use SebastianBergmann\CodeCoverage\Data\ProcessedCodeCoverageData;
use SebastianBergmann\CodeCoverage\Data\RawCodeCoverageData;
use SebastianBergmann\CodeCoverage\StaticAnalysis\AnalysisResult;
use SebastianBergmann\CodeCoverage\StaticAnalysis\FileAnalyser;
use SebastianBergmann\CodeCoverage\Test\Target\Mapper;
use SebastianBergmann\CodeCoverage\Test\Target\TargetCollection;
use SebastianBergmann\CodeCoverage\Test\TestSize;

/**
 * @internal This class is not covered by the backward compatibility promise for phpunit/php-code-coverage
 *
 * @no-named-arguments Parameter names are not covered by the backward compatibility promise for phpunit/php-code-coverage
 *
 * @phpstan-import-type TargetedLines from CodeCoverage
 */
final readonly class FilterProcessor
{
    /**
     * @param false|TargetedLines $linesToBeCovered
     * @param TargetedLines       $linesToBeUsed
     * @param list<class-string>  $parentClassesExcludedFromUnintentionallyCoveredCodeCheck
     *
     * @throws ReflectionException
     * @throws UnintentionallyCoveredCodeException
     */
    public function applyCoversAndUsesFilter(RawCodeCoverageData $rawData, array|false $linesToBeCovered, array $linesToBeUsed, TestSize $size, bool $checkForUnintentionallyCoveredCode, Mapper $targetMapper, array $parentClassesExcludedFromUnintentionallyCoveredCodeCheck, TargetCollection $covers, TargetCollection $uses): void
    {
        if ($linesToBeCovered === false) {
            $rawData->clear();

            return;
        }

        if ($linesToBeCovered === []) {
            return;
        }

        if ($checkForUnintentionallyCoveredCode && !$size->isMedium() && !$size->isLarge()) {
            (new UnintentionallyCoveredCodeChecker)->check(
                $rawData,
                $linesToBeCovered,
                $linesToBeUsed,
                $targetMapper,
                $parentClassesExcludedFromUnintentionallyCoveredCodeCheck,
                $covers,
                $uses,
            );
        }

        $rawLineData         = $rawData->lineCoverage();
        $filesWithNoCoverage = array_diff_key($rawLineData, $linesToBeCovered);

        foreach (array_keys($filesWithNoCoverage) as $fileWithNoCoverage) {
            $rawData->removeCoverageDataForFile($fileWithNoCoverage);
        }

        foreach ($linesToBeCovered as $fileToBeCovered => $includedLines) {
            $includedLines = array_flip($includedLines);

            $rawData->keepLineCoverageDataOnlyForLines($fileToBeCovered, $includedLines);
            $rawData->keepFunctionCoverageDataOnlyForLines($fileToBeCovered, $includedLines);
        }
    }

    public function applyFilter(RawCodeCoverageData $data, Filter $filter): void
    {
        if (!$filter->isEmpty()) {
            foreach (array_keys($data->lineCoverage()) as $filename) {
                if ($filter->isExcluded($filename)) {
                    $data->removeCoverageDataForFile($filename);
                }
            }
        }

        $data->skipEmptyLines();
    }

    public function applyExecutableLinesFilter(RawCodeCoverageData $data, Filter $filter, FileAnalyser $analyser): void
    {
        foreach (array_keys($data->lineCoverage()) as $filename) {
            if (!$filter->isFile($filename)) {
                continue;
            }

            $analysisResult = $analyser->analyse($filename);

            if (!$analysisResult->wasParsed()) {
                continue;
            }

            $linesToBranchMap = $analysisResult->executableLines();

            $data->keepLineCoverageDataOnlyForLines(
                $filename,
                $linesToBranchMap,
            );

            $data->addMissingExecutableLines(
                $filename,
                $analysisResult->branchOperatorLines(),
            );

            $data->markExecutableLineByBranch(
                $filename,
                $linesToBranchMap,
            );

            $data->markLinesAsNotExecutable(
                $filename,
                $analysisResult->deadLines(),
            );
        }
    }

    public function applyIgnoredLinesFilter(RawCodeCoverageData $data, Filter $filter, FileAnalyser $analyser): void
    {
        foreach (array_keys($data->lineCoverage()) as $filename) {
            if (!$filter->isFile($filename)) {
                continue;
            }

            $data->removeCoverageDataForLines(
                $filename,
                $analyser->analyse($filename)->ignoredLines(),
            );
        }
    }

    /**
     * @return list<RawCodeCoverageData>
     */
    public function uncoveredFilesFromFilter(Filter $filter, ProcessedCodeCoverageData $data, FileAnalyser $analyser): array
    {
        $uncoveredFiles = array_diff(
            $filter->files(),
            $data->coveredFiles(),
        );

        $result = [];

        foreach ($uncoveredFiles as $uncoveredFile) {
            if (is_file($uncoveredFile)) {
                $result[] = RawCodeCoverageData::fromUncoveredFile(
                    $uncoveredFile,
                    $analyser,
                );
            }
        }

        return $result;
    }

    /**
     * Returns data seeded from static analysis for the functions and methods for which no line
     * is known in files for which data is known, one entry per function or method.
     *
     * Such a function or method was not executed, otherwise the driver would have reported its
     * lines. Xdebug 3.6, for instance, does not report the lines of a function that was compiled
     * before the collection of code coverage data was started unless a file is compiled while
     * code coverage data is collected. The data is seeded the same way as the data for a file
     * that was not executed, see uncoveredFilesFromFilter().
     *
     * @return array<non-empty-string, list<array<positive-int, int>>>
     */
    public function unreportedCodeUnits(Filter $filter, ProcessedCodeCoverageData $data, FileAnalyser $analyser, bool $useAnnotationsForIgnoringCode): array
    {
        $result = [];

        foreach ($data->lineCoverage() as $file => $knownLines) {
            if (!$filter->isFile($file)) {
                continue;
            }

            $analysisResult = $analyser->analyse($file);

            if (!$analysisResult->wasParsed()) {
                continue;
            }

            $unreportedCodeUnits = [];

            foreach ($this->linesOfFunctionsAndMethods($analysisResult) as [$startLine, $endLine]) {
                if (!$this->anyLineIsKnown($knownLines, $startLine, $endLine)) {
                    $unreportedCodeUnits[] = [$startLine, $endLine];
                }
            }

            if ($unreportedCodeUnits === []) {
                continue;
            }

            $seededData = RawCodeCoverageData::fromUncoveredFile($file, $analyser);

            $this->applyFilter($seededData, $filter);
            $this->applyExecutableLinesFilter($seededData, $filter, $analyser);

            if ($useAnnotationsForIgnoringCode) {
                $this->applyIgnoredLinesFilter($seededData, $filter, $analyser);
            }

            $seededLines = $seededData->lineCoverage()[$file] ?? [];

            foreach ($unreportedCodeUnits as [$startLine, $endLine]) {
                $lines = [];

                foreach ($seededLines as $line => $status) {
                    if ($line >= $startLine && $line <= $endLine) {
                        $lines[$line] = $status;
                    }
                }

                if ($lines !== []) {
                    $result[$file][] = $lines;
                }
            }
        }

        return $result;
    }

    /**
     * @return list<array{positive-int, positive-int}>
     */
    private function linesOfFunctionsAndMethods(AnalysisResult $analysisResult): array
    {
        $lines = [];

        foreach ($analysisResult->functions() as $function) {
            $lines[] = [$function->startLine(), $function->endLine()];
        }

        foreach ($analysisResult->classes() as $class) {
            foreach ($class->methods() as $method) {
                $lines[] = [$method->startLine(), $method->endLine()];
            }
        }

        foreach ($analysisResult->traits() as $trait) {
            foreach ($trait->methods() as $method) {
                $lines[] = [$method->startLine(), $method->endLine()];
            }
        }

        usort($lines, static fn (array $a, array $b): int => $a[0] <=> $b[0]);

        return $lines;
    }

    /**
     * @param array<positive-int, mixed> $knownLines
     */
    private function anyLineIsKnown(array $knownLines, int $startLine, int $endLine): bool
    {
        for ($line = $startLine; $line <= $endLine; $line++) {
            if (array_key_exists($line, $knownLines)) {
                return true;
            }
        }

        return false;
    }
}
