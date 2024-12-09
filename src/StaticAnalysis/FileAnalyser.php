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
 *
 * @phpstan-type LinesType array<int, int>
 */
interface FileAnalyser
{
    /**
     * @return array<string, Interface_>
     */
    public function interfacesIn(string $filename): array;

    /**
     * @return array<string, Class_>
     */
    public function classesIn(string $filename): array;

    /**
     * @return array<string, Trait_>
     */
    public function traitsIn(string $filename): array;

    /**
     * @return array<string, Function_>
     */
    public function functionsIn(string $filename): array;

    public function linesOfCodeFor(string $filename): LinesOfCode;

    /**
     * @return LinesType
     */
    public function executableLinesIn(string $filename): array;

    /**
     * @return LinesType
     */
    public function ignoredLinesFor(string $filename): array;
}
