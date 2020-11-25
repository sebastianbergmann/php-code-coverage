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

use PHPUnit\Framework\TestCase;
use PHPUnit\Runner\PhptTestCase;
use SebastianBergmann\CodeCoverage\Node\Directory;

interface CodeCoverageInterface
{
    /**
     * Returns the code coverage information as a graph of node objects.
     */
    public function getReport(): Directory;

    /**
     * Clears collected code coverage data.
     */
    public function clear(): void;

    /**
     * Returns the filter object used.
     */
    public function filter(): Filter;

    /**
     * Returns the collected code coverage data.
     */
    public function getData(bool $raw = false): ProcessedCodeCoverageData;

    /**
     * Sets the coverage data.
     */
    public function setData(ProcessedCodeCoverageData $data): void;

    /**
     * Returns the test data.
     */
    public function getTests(): array;

    /**
     * Sets the test data.
     */
    public function setTests(array $tests): void;

    /**
     * Start collection of code coverage information.
     *
     * @param PhptTestCase|string|TestCase $id
     */
    public function start($id, bool $clear = false): void;

    /**
     * Stop collection of code coverage information.
     *
     * @param array|false $linesToBeCovered
     */
    public function stop(bool $append = true, $linesToBeCovered = [], array $linesToBeUsed = []): RawCodeCoverageData;

    /**
     * Appends code coverage data.
     *
     * @param PhptTestCase|string|TestCase $id
     * @param array|false                  $linesToBeCovered
     *
     * @throws UnintentionallyCoveredCodeException
     * @throws TestIdMissingException
     * @throws ReflectionException
     */
    public function append(RawCodeCoverageData $rawData, $id = null, bool $append = true, $linesToBeCovered = [], array $linesToBeUsed = []): void;

    /**
     * Merges the data from another instance.
     */
    public function merge(self $that): void;

    public function enableCheckForUnintentionallyCoveredCode(): void;

    public function disableCheckForUnintentionallyCoveredCode(): void;

    public function includeUncoveredFiles(): void;

    public function excludeUncoveredFiles(): void;

    public function processUncoveredFiles(): void;

    public function doNotProcessUncoveredFiles(): void;

    public function enableAnnotationsForIgnoringCode(): void;

    public function disableAnnotationsForIgnoringCode(): void;

    public function ignoreDeprecatedCode(): void;

    public function doNotIgnoreDeprecatedCode(): void;

    /**
     * @psalm-assert-if-true !null $this->cacheDirectory
     */
    public function cachesStaticAnalysis(): bool;

    public function cacheStaticAnalysis(string $directory): void;

    public function doNotCacheStaticAnalysis(): void;

    /**
     * @throws StaticAnalysisCacheNotConfiguredException
     */
    public function cacheDirectory(): string;

    /**
     * @psalm-param class-string $className
     */
    public function excludeSubclassesOfThisClassFromUnintentionallyCoveredCodeCheck(string $className): void;

    public function enableBranchAndPathCoverage(): void;

    public function disableBranchAndPathCoverage(): void;

    public function collectsBranchAndPathCoverage(): bool;

    public function detectsDeadCode(): bool;
}
