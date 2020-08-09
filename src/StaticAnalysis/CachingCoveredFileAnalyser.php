<?php declare(strict_types=1);
/*
 * This file is part of phpunit/php-code-coverage.
 *
 * (c) Sebastian Bergmann <sebastian@phpunit.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace SebastianBergmann\CodeCoverage\StaticAnalysis;

use SebastianBergmann\LinesOfCode\LinesOfCode;

final class CachingCoveredFileAnalyser extends Cache implements CoveredFileAnalyser
{
    /**
     * @var CoveredFileAnalyser
     */
    private $coveredFileAnalyser;

    public function __construct(string $directory, CoveredFileAnalyser $coveredFileAnalyser)
    {
        parent::__construct($directory);

        $this->coveredFileAnalyser = $coveredFileAnalyser;
    }

    public function classesIn(string $filename): array
    {
        if ($this->cacheHas($filename, __METHOD__)) {
            return $this->cacheRead($filename, __METHOD__);
        }

        $data = $this->coveredFileAnalyser->classesIn($filename);

        $this->cacheWrite($filename, __METHOD__, $data);

        return $data;
    }

    public function traitsIn(string $filename): array
    {
        if ($this->cacheHas($filename, __METHOD__)) {
            return $this->cacheRead($filename, __METHOD__);
        }

        $data = $this->coveredFileAnalyser->traitsIn($filename);

        $this->cacheWrite($filename, __METHOD__, $data);

        return $data;
    }

    public function functionsIn(string $filename): array
    {
        if ($this->cacheHas($filename, __METHOD__)) {
            return $this->cacheRead($filename, __METHOD__);
        }

        $data = $this->coveredFileAnalyser->functionsIn($filename);

        $this->cacheWrite($filename, __METHOD__, $data);

        return $data;
    }

    public function linesOfCodeFor(string $filename): LinesOfCode
    {
        if ($this->cacheHas($filename, __METHOD__)) {
            return $this->cacheRead($filename, __METHOD__, [LinesOfCode::class]);
        }

        $data = $this->coveredFileAnalyser->linesOfCodeFor($filename);

        $this->cacheWrite($filename, __METHOD__, $data);

        return $data;
    }

    public function ignoredLinesFor(string $filename): array
    {
        if ($this->cacheHas($filename, __METHOD__)) {
            return $this->cacheRead($filename, __METHOD__);
        }

        $data = $this->coveredFileAnalyser->ignoredLinesFor($filename);

        $this->cacheWrite($filename, __METHOD__, $data);

        return $data;
    }
}
