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
    private UncoveredFileAnalyser $uncoveredFileAnalyser;

    /**
     * @var array
     */
    private $cache = [];

    public function __construct(string $directory, UncoveredFileAnalyser $uncoveredFileAnalyser)
    {
        parent::__construct($directory);

        $this->uncoveredFileAnalyser = $uncoveredFileAnalyser;
    }

    public function executableLinesIn(string $filename): array
    {
        if (isset($this->cache[$filename])) {
            return $this->cache[$filename];
        }

        if ($this->has($filename, __METHOD__)) {
            $this->cache[$filename] = $this->read($filename, __METHOD__);

            return $this->cache[$filename];
        }

        $this->cache[$filename] = $this->uncoveredFileAnalyser->executableLinesIn($filename);

        $this->write($filename, __METHOD__, $this->cache[$filename]);

        return $this->cache[$filename];
    }
}
