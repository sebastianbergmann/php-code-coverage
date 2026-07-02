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
use SebastianBergmann\CodeCoverage\Driver\Granularity;
use SebastianBergmann\CodeCoverage\Node\Builder;
use SebastianBergmann\CodeCoverage\Node\Directory;
use SebastianBergmann\CodeCoverage\StaticAnalysis\FileAnalyser;
use SebastianBergmann\CodeCoverage\StaticAnalysis\Registry;
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
    private ?Mapper $targetMapper = null;

    /**
     * @var ?non-empty-string
     */
    private ?string $cacheDirectory                  = null;
    private bool $checkForUnintentionallyCoveredCode = false;
    private bool $includeUncoveredFiles              = true;
    private bool $ignoreDeprecatedCode               = false;
    private bool $useAnnotationsForIgnoringCode      = true;

    /**
     * @var list<class-string>
     */
    private array $parentClassesExcludedFromUnintentionallyCoveredCodeCheck = [];

    /**
     * @var ?non-empty-string
     */
    private ?string $currentId     = null;
    private ?TestSize $currentSize = null;
    private ProcessedCodeCoverageData $data;

    /**
     * @var array<non-empty-string, TestType>
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
     * @return array<non-empty-string, TestType>
     */
    public function getTests(): array
    {
        return $this->tests;
    }

    /**
     * @param array<non-empty-string, TestType> $tests
     */
    public function setTests(array $tests): void
    {
        $this->tests = $tests;
    }

    /**
     * @param non-empty-string $id
     */
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
     * @param ?non-empty-string $id
     *
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

        if ($covers !== false) {
            $linesToBeCovered = [];

            if ($covers->isNotEmpty()) {
                $linesToBeCovered = $this->targetMapper()->mapTargets($covers);
            }
        } else {
            $covers = TargetCollection::fromArray([]);
        }

        $linesToBeUsed = [];

        if ($linesToBeCovered !== false && $linesToBeCovered !== [] && $uses->isNotEmpty()) {
            $linesToBeUsed = $this->targetMapper()->mapTargets($uses);
        }

        if ($linesToBeCovered === false) {
            $rawData->clear();
        } elseif ($linesToBeCovered !== []) {
            $filterProcessor->applyCoversAndUsesFilter(
                $rawData,
                $linesToBeCovered,
                $linesToBeUsed,
                $size,
                $this->checkForUnintentionallyCoveredCode,
                $this->targetMapper(),
                $this->parentClassesExcludedFromUnintentionallyCoveredCodeCheck,
                $covers,
                $uses,
            );
        }

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

    /**
     * @param non-empty-string $directory
     */
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
     *
     * @return non-empty-string
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

    /**
     * @throws BranchCoverageNotSupportedException
     * @throws PathCoverageNotSupportedException
     *
     * @deprecated
     */
    public function enableBranchAndPathCoverage(): void
    {
        $this->driver->setGranularity(Granularity::LineBranchAndPath);
    }

    /**
     * @deprecated
     */
    public function disableBranchAndPathCoverage(): void
    {
        $this->driver->setGranularity(Granularity::Line);
    }

    /**
     * @deprecated
     */
    public function collectsBranchAndPathCoverage(): bool
    {
        return $this->driver->granularity() === Granularity::LineBranchAndPath;
    }

    public function validate(TargetCollection $targets): ValidationResult
    {
        if ($targets->isEmpty()) {
            return ValidationResult::success();
        }

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
        return Registry::analyser(
            $this->cacheDirectory,
            $this->useAnnotationsForIgnoringCode,
            $this->ignoreDeprecatedCode,
        );
    }
}
