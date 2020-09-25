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

    public function __construct(string $directory, CoveredFileAnalyser $coveredFileAnalyser, bool $validate = true)
    {
        parent::__construct($directory, $validate);

        $this->coveredFileAnalyser = $coveredFileAnalyser;
    }

    public function classesIn(string $filename): array
    {
        if ($this->has($filename, 'classes')) {
            return $this->read($filename, 'classes');
        }

        $data = $this->coveredFileAnalyser->classesIn($filename);

        $this->write($filename, 'classes', $data);

        return $data;
    }

    public function traitsIn(string $filename): array
    {
        if ($this->has($filename, 'traits')) {
            return $this->read($filename, 'traits');
        }

        $data = $this->coveredFileAnalyser->traitsIn($filename);

        $this->write($filename, 'traits', $data);

        return $data;
    }

    public function functionsIn(string $filename): array
    {
        if ($this->has($filename, 'functions')) {
            return $this->read($filename, 'functions');
        }

        $data = $this->coveredFileAnalyser->functionsIn($filename);

        $this->write($filename, 'functions', $data);

        return $data;
    }

    public function linesOfCodeFor(string $filename): LinesOfCode
    {
        if ($this->has($filename, 'linesOfCode')) {
            return $this->read($filename, 'linesOfCode', [LinesOfCode::class]);
        }

        $data = $this->coveredFileAnalyser->linesOfCodeFor($filename);

        $this->write($filename, 'linesOfCode', $data);

        return $data;
    }

    public function ignoredLinesFor(string $filename): array
    {
        if ($this->has($filename, 'ignoredLines')) {
            return $this->read($filename, 'ignoredLines');
        }

        $data = $this->coveredFileAnalyser->ignoredLinesFor($filename);

        $this->write($filename, 'ignoredLines', $data);

        return $data;
    }
}
