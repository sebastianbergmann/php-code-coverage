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

    /**
     * @var array
     *
     * @see https://xdebug.org/docs/code_coverage for format
     */
    private $functionCoverage = [];

    public static function fromXdebugWithoutPathCoverage(array $rawCoverage): self
    {
        return new self($rawCoverage, []);
    }

    public static function fromXdebugWithPathCoverage(array $rawCoverage): self
    {
        $lineCoverage     = [];
        $functionCoverage = [];

        foreach ($rawCoverage as $file => $fileCoverageData) {
            if (isset($fileCoverageData['functions'])) {
                $lineCoverage[$file]     = $fileCoverageData['lines'];
                $functionCoverage[$file] = $fileCoverageData['functions'];
            } else { // not every file has functions, Xdebug outputs just line data for these
                $lineCoverage[$file] = $fileCoverageData;
            }
        }

        return new self($lineCoverage, $functionCoverage);
    }

    private function __construct(array $lineCoverage, array $functionCoverage)
    {
        $this->lineCoverage     = $lineCoverage;
        $this->functionCoverage = $functionCoverage;
    }

    public function clear(): void
    {
        $this->lineCoverage = $this->functionCoverage = [];
    }

    public function getLineCoverage(): array
    {
        return $this->lineCoverage;
    }

    public function removeCoverageDataForFile(string $filename): void
    {
        unset($this->lineCoverage[$filename], $this->functionCoverage[$filename]);
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
