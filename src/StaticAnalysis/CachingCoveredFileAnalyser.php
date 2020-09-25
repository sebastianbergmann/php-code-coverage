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

/**
 * @internal This class is not covered by the backward compatibility promise for phpunit/php-code-coverage
 */
final class CachingCoveredFileAnalyser extends Cache implements CoveredFileAnalyser
{
    /**
     * @var CoveredFileAnalyser
     */
    private $coveredFileAnalyser;

    /**
     * @var array
     */
    private $inMemoryCacheForIgnoredLines = [];

    public function __construct(string $directory, CoveredFileAnalyser $coveredFileAnalyser, bool $validate = true)
    {
        parent::__construct($directory, $validate);

        $this->coveredFileAnalyser = $coveredFileAnalyser;
    }

    public function classesIn(string $filename): array
    {
        if ($this->has($filename, __METHOD__)) {
            return $this->read($filename, __METHOD__);
        }

        $data = $this->coveredFileAnalyser->classesIn($filename);

        $this->write($filename, __METHOD__, $data);

        return $data;
    }

    public function traitsIn(string $filename): array
    {
        if ($this->has($filename, __METHOD__)) {
            return $this->read($filename, __METHOD__);
        }

        $data = $this->coveredFileAnalyser->traitsIn($filename);

        $this->write($filename, __METHOD__, $data);

        return $data;
    }

    public function functionsIn(string $filename): array
    {
        if ($this->has($filename, __METHOD__)) {
            return $this->read($filename, __METHOD__);
        }

        $data = $this->coveredFileAnalyser->functionsIn($filename);

        $this->write($filename, __METHOD__, $data);

        return $data;
    }

    public function linesOfCodeFor(string $filename): LinesOfCode
    {
        if ($this->has($filename, __METHOD__)) {
            return $this->read($filename, __METHOD__, [LinesOfCode::class]);
        }

        $data = $this->coveredFileAnalyser->linesOfCodeFor($filename);

        $this->write($filename, __METHOD__, $data);

        return $data;
    }

    public function ignoredLinesFor(string $filename): array
    {
        if (isset($this->inMemoryCacheForIgnoredLines[$filename])) {
            return $this->inMemoryCacheForIgnoredLines[$filename];
        }

        if ($this->has($filename, __METHOD__)) {
            return $this->read($filename, __METHOD__);
        }

        $this->inMemoryCacheForIgnoredLines[$filename] = $this->coveredFileAnalyser->ignoredLinesFor($filename);

        $this->write($filename, __METHOD__, $this->inMemoryCacheForIgnoredLines[$filename]);

        return $this->inMemoryCacheForIgnoredLines[$filename];
    }
}
