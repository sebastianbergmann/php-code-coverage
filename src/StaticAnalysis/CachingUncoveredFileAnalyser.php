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

    public function __construct(string $directory, UncoveredFileAnalyser $uncoveredFileAnalyser, bool $validate = true)
    {
        parent::__construct($directory, $validate);

        $this->uncoveredFileAnalyser = $uncoveredFileAnalyser;
    }

    public function executableLinesIn(string $filename): array
    {
        if ($this->has($filename, 'executableLines')) {
            return $this->read($filename, 'executableLines');
        }

        $data = $this->uncoveredFileAnalyser->executableLinesIn($filename);

        $this->write($filename, 'executableLines', $data);

        return $data;
    }
}
