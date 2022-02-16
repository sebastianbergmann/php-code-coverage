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
    private const CACHE_FORMAT_VERSION = 2;

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
        $cacheKey = __CLASS__ . self::CACHE_FORMAT_VERSION;

        if ($this->has($filename, $cacheKey)) {
            return $this->read($filename, $cacheKey);
        }

        $data = $this->uncoveredFileAnalyser->executableLinesIn($filename);

        $this->write($filename, $cacheKey, $data);

        return $data;
    }
}
