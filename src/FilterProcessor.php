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
use function array_keys;
use function is_file;
use SebastianBergmann\CodeCoverage\Data\ProcessedCodeCoverageData;
use SebastianBergmann\CodeCoverage\Data\RawCodeCoverageData;
use SebastianBergmann\CodeCoverage\StaticAnalysis\FileAnalyser;
use SebastianBergmann\CodeCoverage\Test\Target\Mapper;
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
    public function applyCoversAndUsesFilter(RawCodeCoverageData $rawData, array|false $linesToBeCovered, array $linesToBeUsed, TestSize $size, bool $checkForUnintentionallyCoveredCode, Mapper $targetMapper, array $parentClassesExcludedFromUnintentionallyCoveredCodeCheck): void
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
            );
        }

        $rawLineData         = $rawData->lineCoverage();
        $filesWithNoCoverage = array_diff_key($rawLineData, $linesToBeCovered);

        foreach (array_keys($filesWithNoCoverage) as $fileWithNoCoverage) {
            $rawData->removeCoverageDataForFile($fileWithNoCoverage);
        }

        foreach ($linesToBeCovered as $fileToBeCovered => $includedLines) {
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

            $linesToBranchMap = $analyser->analyse($filename)->executableLines();

            $data->keepLineCoverageDataOnlyForLines(
                $filename,
                array_keys($linesToBranchMap),
            );

            $data->markExecutableLineByBranch(
                $filename,
                $linesToBranchMap,
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
}
