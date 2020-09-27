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

/**
 * @internal This class is not covered by the backward compatibility promise for phpunit/php-code-coverage
 */
final class CachingUncoveredFileAnalyser extends Cache implements UncoveredFileAnalyser
{
    /**
     * @var UncoveredFileAnalyser
     */
    private $uncoveredFileAnalyser;

    public function __construct(string $directory, UncoveredFileAnalyser $uncoveredFileAnalyser)
    {
        parent::__construct($directory);

        $this->uncoveredFileAnalyser = $uncoveredFileAnalyser;
    }

    public function executableLinesIn(string $filename): array
    {
        if ($this->has($filename, __METHOD__)) {
            return $this->read($filename, __METHOD__);
        }

        $data = $this->uncoveredFileAnalyser->executableLinesIn($filename);

        $this->write($filename, __METHOD__, $data);

        return $data;
    }
}
