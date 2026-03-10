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

use function array_merge;
use SebastianBergmann\CodeCoverage\Data\ProcessedCodeCoverageData;
use SebastianBergmann\CodeCoverage\Data\RawCodeCoverageData;
use SebastianBergmann\CodeCoverage\Driver\Driver;
use SebastianBergmann\CodeCoverage\Node\Builder;
use SebastianBergmann\CodeCoverage\Node\Directory;
use SebastianBergmann\CodeCoverage\StaticAnalysis\CachingSourceAnalyser;
use SebastianBergmann\CodeCoverage\StaticAnalysis\FileAnalyser;
use SebastianBergmann\CodeCoverage\StaticAnalysis\ParsingSourceAnalyser;
use SebastianBergmann\CodeCoverage\Test\Target\MapBuilder;
use SebastianBergmann\CodeCoverage\Test\Target\Mapper;
use SebastianBergmann\CodeCoverage\Test\Target\TargetCollection;
use SebastianBergmann\CodeCoverage\Test\Target\TargetCollectionValidator;
use SebastianBergmann\CodeCoverage\Test\Target\ValidationResult;
use SebastianBergmann\CodeCoverage\Test\TestSize;
use SebastianBergmann\CodeCoverage\Test\TestStatus;

/**
 * Provides collection functionality for PHP code coverage information.
 *
 * @phpstan-type TestType array{size: string, status: string, time: float}
 * @phpstan-type TargetedLines array<non-empty-string, list<positive-int>>
 *
 * @no-named-arguments Parameter names are not covered by the backward compatibility promise for phpunit/php-code-coverage
 */
final class CodeCoverage
{
    private const string UNCOVERED_FILES = 'UNCOVERED_FILES';
    private readonly Driver $driver;
    private readonly Filter $filter;
    private ?FileAnalyser $analyser                  = null;
    private ?Mapper $targetMapper                    = null;
    private ?string $cacheDirectory                  = null;
    private bool $checkForUnintentionallyCoveredCode = false;
    private bool $collectBranchAndPathCoverage       = false;
    private bool $includeUncoveredFiles              = true;
    private bool $ignoreDeprecatedCode               = false;
    private bool $useAnnotationsForIgnoringCode      = true;

    /**
     * @var list<class-string>
     */
    private array $parentClassesExcludedFromUnintentionallyCoveredCodeCheck = [];
    private ?string $currentId                                              = null;
    private ?TestSize $currentSize                                          = null;
    private ProcessedCodeCoverageData $data;

    /**
     * @var array<string, TestType>
     */
    private array $tests             = [];
    private ?Directory $cachedReport = null;

    public function __construct(Driver $driver, Filter $filter)
    {
        $this->driver = $driver;
        $this->filter = $filter;
        $this->data   = new ProcessedCodeCoverageData;
    }

    /**
     * @internal This method is not covered by the backward compatibility promise for phpunit/php-code-coverage
     */
    public function getReport(): Directory
    {
        if ($this->cachedReport === null) {
            $this->cachedReport = new Builder($this->analyser())->build($this->getData(), $this->tests);
        }

        return $this->cachedReport;
    }

    /**
     * Clears collected code coverage data.
     */
    public function clear(): void
    {
        $this->currentId    = null;
        $this->currentSize  = null;
        $this->data         = new ProcessedCodeCoverageData;
        $this->tests        = [];
        $this->cachedReport = null;
    }

    /**
     * @internal
     */
    public function clearCache(): void
    {
        $this->cachedReport = null;
    }

    /**
     * Returns the filter object used.
     */
    public function filter(): Filter
    {
        return $this->filter;
    }

    /**
     * Returns the collected code coverage data.
     */
    public function getData(bool $raw = false): ProcessedCodeCoverageData
    {
        if (!$raw) {
            if ($this->includeUncoveredFiles) {
                $this->addUncoveredFilesFromFilter();
            }
        }

        return $this->data;
    }

    /**
     * Sets the coverage data.
     */
    public function setData(ProcessedCodeCoverageData $data): void
    {
        $this->data = $data;
    }

    /**
     * @return array<string, TestType>
     */
    public function getTests(): array
    {
        return $this->tests;
    }

    /**
     * @param array<string, TestType> $tests
     */
    public function setTests(array $tests): void
    {
        $this->tests = $tests;
    }

    public function start(string $id, ?TestSize $size = null, bool $clear = false): void
    {
        if ($clear) {
            $this->clear();
        }

        $this->currentId   = $id;
        $this->currentSize = $size;

        $this->driver->start();

        $this->cachedReport = null;
    }

    public function stop(bool $append = true, ?TestStatus $status = null, null|false|TargetCollection $covers = null, ?TargetCollection $uses = null, float $time = 0.0): RawCodeCoverageData
    {
        $data = $this->driver->stop();

        $this->append($data, null, $append, $status, $covers, $uses, $time);

        $this->currentId    = null;
        $this->currentSize  = null;
        $this->cachedReport = null;

        return $data;
    }

    /**
     * @throws ReflectionException
     * @throws TestIdMissingException
     * @throws UnintentionallyCoveredCodeException
     */
    public function append(RawCodeCoverageData $rawData, ?string $id = null, bool $append = true, ?TestStatus $status = null, null|false|TargetCollection $covers = null, ?TargetCollection $uses = null, float $time = 0.0): void
    {
        if ($id === null) {
            $id = $this->currentId;
        }

        if ($id === null) {
            throw new TestIdMissingException;
        }

        if ($status === null) {
            $status = TestStatus::Unknown;
        }

        if ($covers === null) {
            $covers = TargetCollection::fromArray([]);
        }

        if ($uses === null) {
            $uses = TargetCollection::fromArray([]);
        }

        $size = $this->currentSize;

        if ($size === null) {
            $size = TestSize::Unknown;
        }

        $this->cachedReport = null;

        $filterProcessor = new FilterProcessor;

        $filterProcessor->applyFilter($rawData, $this->filter);
        $filterProcessor->applyExecutableLinesFilter($rawData, $this->filter, $this->analyser());

        if ($this->useAnnotationsForIgnoringCode) {
            $filterProcessor->applyIgnoredLinesFilter($rawData, $this->filter, $this->analyser());
        }

        $this->data->initializeUnseenData($rawData);

        if (!$append) {
            return;
        }

        if ($id === self::UNCOVERED_FILES) {
            return;
        }

        $linesToBeCovered = false;
        $linesToBeUsed    = [];

        if ($covers !== false) {
            $linesToBeCovered = $this->targetMapper()->mapTargets($covers);
        }

        if ($linesToBeCovered !== false) {
            $linesToBeUsed = $this->targetMapper()->mapTargets($uses);
        }

        $filterProcessor->applyCoversAndUsesFilter(
            $rawData,
            $linesToBeCovered,
            $linesToBeUsed,
            $size,
            $this->checkForUnintentionallyCoveredCode,
            $this->targetMapper,
            $this->parentClassesExcludedFromUnintentionallyCoveredCodeCheck,
        );

        if ($rawData->lineCoverage() === []) {
            return;
        }

        $this->tests[$id] = [
            'size'   => $size->asString(),
            'status' => $status->asString(),
            'time'   => $time,
        ];

        $this->data->markCodeAsExecutedByTestCase($id, $rawData);
    }

    public function merge(self $that): void
    {
        $this->filter->includeFiles(
            $that->filter()->files(),
        );

        $this->data->merge($that->data);

        $this->tests = array_merge($this->tests, $that->getTests());

        $this->cachedReport = null;
    }

    public function enableCheckForUnintentionallyCoveredCode(): void
    {
        $this->checkForUnintentionallyCoveredCode = true;
    }

    public function disableCheckForUnintentionallyCoveredCode(): void
    {
        $this->checkForUnintentionallyCoveredCode = false;
    }

    public function includeUncoveredFiles(): void
    {
        $this->includeUncoveredFiles = true;
    }

    public function excludeUncoveredFiles(): void
    {
        $this->includeUncoveredFiles = false;
    }

    public function enableAnnotationsForIgnoringCode(): void
    {
        $this->useAnnotationsForIgnoringCode = true;
    }

    public function disableAnnotationsForIgnoringCode(): void
    {
        $this->useAnnotationsForIgnoringCode = false;
    }

    public function ignoreDeprecatedCode(): void
    {
        $this->ignoreDeprecatedCode = true;
    }

    public function doNotIgnoreDeprecatedCode(): void
    {
        $this->ignoreDeprecatedCode = false;
    }

    /**
     * @phpstan-assert-if-true !null $this->cacheDirectory
     */
    public function cachesStaticAnalysis(): bool
    {
        return $this->cacheDirectory !== null;
    }

    public function cacheStaticAnalysis(string $directory): void
    {
        $this->cacheDirectory = $directory;
    }

    public function doNotCacheStaticAnalysis(): void
    {
        $this->cacheDirectory = null;
    }

    /**
     * @throws StaticAnalysisCacheNotConfiguredException
     */
    public function cacheDirectory(): string
    {
        if (!$this->cachesStaticAnalysis()) {
            throw new StaticAnalysisCacheNotConfiguredException(
                'The static analysis cache is not configured',
            );
        }

        return $this->cacheDirectory;
    }

    /**
     * @param class-string $className
     */
    public function excludeSubclassesOfThisClassFromUnintentionallyCoveredCodeCheck(string $className): void
    {
        $this->parentClassesExcludedFromUnintentionallyCoveredCodeCheck[] = $className;
    }

    public function enableBranchAndPathCoverage(): void
    {
        $this->driver->enableBranchAndPathCoverage();

        $this->collectBranchAndPathCoverage = true;
    }

    public function disableBranchAndPathCoverage(): void
    {
        $this->driver->disableBranchAndPathCoverage();

        $this->collectBranchAndPathCoverage = false;
    }

    public function collectsBranchAndPathCoverage(): bool
    {
        return $this->collectBranchAndPathCoverage;
    }

    public function validate(TargetCollection $targets): ValidationResult
    {
        return (new TargetCollectionValidator)->validate($this->targetMapper(), $targets);
    }

    /**
     * @return array{name: non-empty-string, version: non-empty-string}
     *
     * @internal This method is not covered by the backward compatibility promise for phpunit/php-code-coverage
     */
    public function driverInformation(): array
    {
        return [
            'name'    => $this->driver->name(),
            'version' => $this->driver->version(),
        ];
    }

    /**
     * @throws UnintentionallyCoveredCodeException
     */
    private function addUncoveredFilesFromFilter(): void
    {
        $uncoveredFilesData = (new FilterProcessor)->uncoveredFilesFromFilter(
            $this->filter,
            $this->data,
            $this->analyser(),
        );

        foreach ($uncoveredFilesData as $rawData) {
            $this->append($rawData, self::UNCOVERED_FILES);
        }
    }

    private function targetMapper(): Mapper
    {
        if ($this->targetMapper !== null) {
            return $this->targetMapper;
        }

        $this->targetMapper = new Mapper(
            (new MapBuilder)->build($this->filter, $this->analyser()),
        );

        return $this->targetMapper;
    }

    private function analyser(): FileAnalyser
    {
        if ($this->analyser !== null) {
            return $this->analyser;
        }

        $sourceAnalyser = new ParsingSourceAnalyser;

        if ($this->cachesStaticAnalysis()) {
            $sourceAnalyser = new CachingSourceAnalyser(
                $this->cacheDirectory,
                $sourceAnalyser,
            );
        }

        $this->analyser = new FileAnalyser(
            $sourceAnalyser,
            $this->useAnnotationsForIgnoringCode,
            $this->ignoreDeprecatedCode,
        );

        return $this->analyser;
    }
}
