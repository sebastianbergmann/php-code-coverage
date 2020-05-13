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

/**
 * Raw context-less code coverage data for SUT.
 */
final class RawCodeCoverageData
{
    /**
     * Line coverage data.
     *
     * @see https://xdebug.org/docs/code_coverage for format
     *
     * @var array
     */
    private $lineData = [];

    public function __construct(array $rawCoverage = [])
    {
        foreach ($rawCoverage as $file => $fileCoverageData) {
            $hasOnlyIntegerKeys = \count(\array_filter(\array_keys($fileCoverageData), 'is_int')) === \count($fileCoverageData);

            if ($hasOnlyIntegerKeys) {
                $this->lineData[$file] = $fileCoverageData;
            } elseif (\count($fileCoverageData) === 2 && isset($fileCoverageData['lines'], $fileCoverageData['functions'])) {
                $this->lineData[$file] = $fileCoverageData['lines'];
            } else {
                throw UnknownCoverageDataFormatException::create($file);
            }
        }
    }

    public function clear(): void
    {
        $this->lineData = [];
    }

    public function getLineData(): array
    {
        return $this->lineData;
    }

    public function removeCoverageDataForFile(string $filename): void
    {
        unset($this->lineData[$filename]);
    }

    /**
     * @param int[] $lines
     */
    public function keepCoverageDataOnlyForLines(string $filename, array $lines): void
    {
        $this->lineData[$filename] = \array_intersect_key($this->lineData[$filename], \array_flip($lines));
    }

    /**
     * @param int[] $lines
     */
    public function removeCoverageDataForLines(string $filename, array $lines): void
    {
        $this->lineData[$filename] = \array_diff_key($this->lineData[$filename], \array_flip($lines));
    }
}
