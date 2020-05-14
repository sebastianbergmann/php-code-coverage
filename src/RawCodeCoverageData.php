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
     * @var array
     *
     * @see https://xdebug.org/docs/code_coverage for format
     */
    private $lineCoverage = [];

    public function __construct(array $rawCoverage = [])
    {
        foreach ($rawCoverage as $file => $fileCoverageData) {
            $hasOnlyIntegerKeys = \count(\array_filter(\array_keys($fileCoverageData), 'is_int')) === \count($fileCoverageData);

            if ($hasOnlyIntegerKeys) {
                $this->lineCoverage[$file] = $fileCoverageData;
            } elseif (\count($fileCoverageData) === 2 && isset($fileCoverageData['lines'], $fileCoverageData['functions'])) {
                $this->lineCoverage[$file] = $fileCoverageData['lines'];
            } else {
                throw UnknownCoverageDataFormatException::create($file);
            }
        }
    }

    public function clear(): void
    {
        $this->lineCoverage = [];
    }

    public function getLineCoverage(): array
    {
        return $this->lineCoverage;
    }

    public function removeCoverageDataForFile(string $filename): void
    {
        unset($this->lineCoverage[$filename]);
    }

    /**
     * @param int[] $lines
     */
    public function keepCoverageDataOnlyForLines(string $filename, array $lines): void
    {
        $this->lineCoverage[$filename] = \array_intersect_key(
            $this->lineCoverage[$filename],
            \array_flip($lines)
        );
    }

    /**
     * @param int[] $lines
     */
    public function removeCoverageDataForLines(string $filename, array $lines): void
    {
        $this->lineCoverage[$filename] = \array_diff_key(
            $this->lineCoverage[$filename],
            \array_flip($lines)
        );
    }
}
