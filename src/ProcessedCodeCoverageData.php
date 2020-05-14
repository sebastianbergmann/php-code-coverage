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

use SebastianBergmann\CodeCoverage\Driver\Driver;

/**
 * Processed/context-added code coverage data for SUT.
 */
final class ProcessedCodeCoverageData
{
    /**
     * Line coverage data.
     * An array of filenames, each having an array of linenumbers, each executable line having an array of testcase ids.
     *
     * @var array
     */
    private $lineCoverage = [];

    public function initializeFilesThatAreSeenTheFirstTime(RawCodeCoverageData $rawData): void
    {
        foreach ($rawData->getLineCoverage() as $file => $lines) {
            if (!isset($this->lineCoverage[$file])) {
                $this->lineCoverage[$file] = [];

                foreach ($lines as $k => $v) {
                    $this->lineCoverage[$file][$k] = $v === Driver::LINE_NOT_EXECUTABLE ? null : [];
                }
            }
        }
    }

    public function markCodeAsExecutedByTestCase(string $testCaseId, RawCodeCoverageData $executedCode): void
    {
        foreach ($executedCode->getLineCoverage() as $file => $lines) {
            foreach ($lines as $k => $v) {
                if ($v === Driver::LINE_EXECUTED) {
                    if (empty($this->lineCoverage[$file][$k]) || !\in_array($testCaseId, $this->lineCoverage[$file][$k], true)) {
                        $this->lineCoverage[$file][$k][] = $testCaseId;
                    }
                }
            }
        }
    }

    public function setLineCoverage(array $lineCoverage): void
    {
        $this->lineCoverage = $lineCoverage;
    }

    public function getLineCoverage(): array
    {
        \ksort($this->lineCoverage);

        return $this->lineCoverage;
    }

    public function getCoveredFiles(): array
    {
        return \array_keys($this->lineCoverage);
    }

    public function renameFile(string $oldFile, string $newFile): void
    {
        $this->lineCoverage[$newFile] = $this->lineCoverage[$oldFile];
        unset($this->lineCoverage[$oldFile]);
    }

    public function merge(self $newData): void
    {
        foreach ($newData->lineCoverage as $file => $lines) {
            if (!isset($this->lineCoverage[$file])) {
                $this->lineCoverage[$file] = $lines;

                continue;
            }

            // we should compare the lines if any of two contains data
            $compareLineNumbers = \array_unique(
                \array_merge(
                    \array_keys($this->lineCoverage[$file]),
                    \array_keys($newData->lineCoverage[$file])
                )
            );

            foreach ($compareLineNumbers as $line) {
                $thatPriority = $this->getLinePriority($newData->lineCoverage[$file], $line);
                $thisPriority = $this->getLinePriority($this->lineCoverage[$file], $line);

                if ($thatPriority > $thisPriority) {
                    $this->lineCoverage[$file][$line] = $newData->lineCoverage[$file][$line];
                } elseif ($thatPriority === $thisPriority && \is_array($this->lineCoverage[$file][$line])) {
                    $this->lineCoverage[$file][$line] = \array_unique(
                        \array_merge($this->lineCoverage[$file][$line], $newData->lineCoverage[$file][$line])
                    );
                }
            }
        }
    }

    /**
     * Determine the priority for a line
     *
     * 1 = the line is not set
     * 2 = the line has not been tested
     * 3 = the line is dead code
     * 4 = the line has been tested
     *
     * During a merge, a higher number is better.
     */
    private function getLinePriority(array $data, int $line): int
    {
        if (!\array_key_exists($line, $data)) {
            return 1;
        }

        if (\is_array($data[$line]) && \count($data[$line]) === 0) {
            return 2;
        }

        if ($data[$line] === null) {
            return 3;
        }

        return 4;
    }
}
