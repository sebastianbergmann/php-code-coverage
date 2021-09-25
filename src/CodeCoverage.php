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

use function array_diff;
use function array_diff_key;
use function array_flip;
use function array_keys;
use function array_merge;
use function array_unique;
use function array_values;
use function count;
use function explode;
use function get_class;
use function is_array;
use function is_file;
use function sort;
use PHPUnit\Framework\TestCase;
use PHPUnit\Runner\PhptTestCase;
use ReflectionClass;
use SebastianBergmann\CodeCoverage\Driver\Driver;
use SebastianBergmann\CodeCoverage\Node\Builder;
use SebastianBergmann\CodeCoverage\Node\Directory;
use SebastianBergmann\CodeCoverage\StaticAnalysis\CachingCoveredFileAnalyser;
use SebastianBergmann\CodeCoverage\StaticAnalysis\CachingUncoveredFileAnalyser;
use SebastianBergmann\CodeCoverage\StaticAnalysis\CoveredFileAnalyser;
use SebastianBergmann\CodeCoverage\StaticAnalysis\ParsingCoveredFileAnalyser;
use SebastianBergmann\CodeCoverage\StaticAnalysis\ParsingUncoveredFileAnalyser;
use SebastianBergmann\CodeCoverage\StaticAnalysis\UncoveredFileAnalyser;
use SebastianBergmann\CodeUnitReverseLookup\Wizard;

/**
 * Provides collection functionality for PHP code coverage information.
 */
final class CodeCoverage
{
    private const UNCOVERED_FILES = 'UNCOVERED_FILES';

    private Driver $driver;

    private Filter $filter;

    private Wizard $wizard;

    private bool $checkForUnintentionallyCoveredCode = false;

    private bool $includeUncoveredFiles = true;

    private bool $ignoreDeprecatedCode = false;

    private PhptTestCase|string|TestCase|null $currentId;

    private ProcessedCodeCoverageData $data;

    private bool $useAnnotationsForIgnoringCode = true;

    private array $tests = [];

    /**
     * @psalm-var list<class-string>
     */
    private array $parentClassesExcludedFromUnintentionallyCoveredCodeCheck = [];

    private ?CoveredFileAnalyser $coveredFileAnalyser = null;

    private ?UncoveredFileAnalyser $uncoveredFileAnalyser = null;

    private ?string $cacheDirectory = null;

    public function __construct(Driver $driver, Filter $filter)
    {
        $this->driver = $driver;
        $this->filter = $filter;
        $this->data   = new ProcessedCodeCoverageData;
        $this->wizard = new Wizard;
    }

    /**
     * Returns the code coverage information as a graph of node objects.
     */
    public function getReport(): Directory
    {
        return (new Builder($this->coveredFileAnalyser()))->build($this);
    }

    /**
     * Clears collected code coverage data.
     */
    public function clear(): void
    {
        $this->currentId = null;
        $this->data      = new ProcessedCodeCoverageData;
        $this->tests     = [];
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
            } else {
                $this->data->removeFilesWithNoCoverage();
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
     * Returns the test data.
     */
    public function getTests(): array
    {
        return $this->tests;
    }

    /**
     * Sets the test data.
     */
    public function setTests(array $tests): void
    {
        $this->tests = $tests;
    }

    public function start(PhptTestCase|string|TestCase $id, bool $clear = false): void
    {
        if ($clear) {
            $this->clear();
        }

        $this->currentId = $id;

        $this->driver->start();
    }

    public function stop(bool $append = true, array|false $linesToBeCovered = [], array $linesToBeUsed = []): RawCodeCoverageData
    {
        if (!is_array($linesToBeCovered) && $linesToBeCovered !== false) {
            throw new InvalidArgumentException(
                '$linesToBeCovered must be an array or false'
            );
        }

        $data = $this->driver->stop();
        $this->append($data, null, $append, $linesToBeCovered, $linesToBeUsed);

        $this->currentId = null;

        return $data;
    }

    /**
     * @throws ReflectionException
     * @throws TestIdMissingException
     * @throws UnintentionallyCoveredCodeException
     */
    public function append(RawCodeCoverageData $rawData, PhptTestCase|string|TestCase|null $id = null, bool $append = true, array|false $linesToBeCovered = [], array $linesToBeUsed = []): void
    {
        if ($id === null) {
            $id = $this->currentId;
        }

        if ($id === null) {
            throw new TestIdMissingException;
        }

        $this->applyFilter($rawData);

        if ($this->useAnnotationsForIgnoringCode) {
            $this->applyIgnoredLinesFilter($rawData);
        }

        $this->data->initializeUnseenData($rawData);

        if (!$append) {
            return;
        }

        if ($id !== self::UNCOVERED_FILES) {
            $this->applyCoversAnnotationFilter(
                $rawData,
                $linesToBeCovered,
                $linesToBeUsed
            );

            if (empty($rawData->lineCoverage())) {
                return;
            }

            $size         = 'unknown';
            $status       = 'unknown';
            $fromTestcase = false;

            if ($id instanceof TestCase) {
                $fromTestcase = true;

                $size   = $id->size()->asString();
                $status = $id->status()->asString();
                $id     = get_class($id) . '::' . $id->getName();
            } elseif ($id instanceof PhptTestCase) {
                $fromTestcase = true;
                $size         = 'large';
                $id           = $id->getName();
            }

            $this->tests[$id] = ['size' => $size, 'status' => $status, 'fromTestcase' => $fromTestcase];

            $this->data->markCodeAsExecutedByTestCase($id, $rawData);
        }
    }

    /**
     * Merges the data from another instance.
     */
    public function merge(self $that): void
    {
        $this->filter->includeFiles(
            $that->filter()->files()
        );

        $this->data->merge($that->data);

        $this->tests = array_merge($this->tests, $that->getTests());
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
     * @psalm-assert-if-true !null $this->cacheDirectory
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
                'The static analysis cache is not configured'
            );
        }

        return $this->cacheDirectory;
    }

    /**
     * @psalm-param class-string $className
     */
    public function excludeSubclassesOfThisClassFromUnintentionallyCoveredCodeCheck(string $className): void
    {
        $this->parentClassesExcludedFromUnintentionallyCoveredCodeCheck[] = $className;
    }

    public function enableBranchAndPathCoverage(): void
    {
        $this->driver->enableBranchAndPathCoverage();
    }

    public function disableBranchAndPathCoverage(): void
    {
        $this->driver->disableBranchAndPathCoverage();
    }

    public function collectsBranchAndPathCoverage(): bool
    {
        return $this->driver->collectsBranchAndPathCoverage();
    }

    public function detectsDeadCode(): bool
    {
        return $this->driver->detectsDeadCode();
    }

    /**
     * @throws ReflectionException
     * @throws UnintentionallyCoveredCodeException
     */
    private function applyCoversAnnotationFilter(RawCodeCoverageData $rawData, array|false $linesToBeCovered, array $linesToBeUsed): void
    {
        if ($linesToBeCovered === false) {
            $rawData->clear();

            return;
        }

        if (empty($linesToBeCovered)) {
            return;
        }

        if ($this->checkForUnintentionallyCoveredCode &&
            (!$this->currentId instanceof TestCase ||
            (!$this->currentId->size()->isMedium() && !$this->currentId->size()->isLarge()))) {
            $this->performUnintentionallyCoveredCodeCheck($rawData, $linesToBeCovered, $linesToBeUsed);
        }

        $rawLineData         = $rawData->lineCoverage();
        $filesWithNoCoverage = array_diff_key($rawLineData, $linesToBeCovered);

        foreach (array_keys($filesWithNoCoverage) as $fileWithNoCoverage) {
            $rawData->removeCoverageDataForFile($fileWithNoCoverage);
        }

        if (is_array($linesToBeCovered)) {
            foreach ($linesToBeCovered as $fileToBeCovered => $includedLines) {
                $rawData->keepCoverageDataOnlyForLines($fileToBeCovered, $includedLines);
            }
        }
    }

    private function applyFilter(RawCodeCoverageData $data): void
    {
        if ($this->filter->isEmpty()) {
            return;
        }

        foreach (array_keys($data->lineCoverage()) as $filename) {
            if ($this->filter->isExcluded($filename)) {
                $data->removeCoverageDataForFile($filename);
            }
        }
    }

    private function applyIgnoredLinesFilter(RawCodeCoverageData $data): void
    {
        foreach (array_keys($data->lineCoverage()) as $filename) {
            if (!$this->filter->isFile($filename)) {
                continue;
            }

            $data->removeCoverageDataForLines(
                $filename,
                $this->coveredFileAnalyser()->ignoredLinesFor($filename)
            );
        }
    }

    /**
     * @throws UnintentionallyCoveredCodeException
     */
    private function addUncoveredFilesFromFilter(): void
    {
        $uncoveredFiles = array_diff(
            $this->filter->files(),
            $this->data->coveredFiles()
        );

        foreach ($uncoveredFiles as $uncoveredFile) {
            if (is_file($uncoveredFile)) {
                $this->append(
                    RawCodeCoverageData::fromUncoveredFile(
                        $uncoveredFile,
                        $this->uncoveredFileAnalyser()
                    ),
                    self::UNCOVERED_FILES
                );
            }
        }
    }

    /**
     * @throws ReflectionException
     * @throws UnintentionallyCoveredCodeException
     */
    private function performUnintentionallyCoveredCodeCheck(RawCodeCoverageData $data, array $linesToBeCovered, array $linesToBeUsed): void
    {
        $allowedLines = $this->getAllowedLines(
            $linesToBeCovered,
            $linesToBeUsed
        );

        $unintentionallyCoveredUnits = [];

        foreach ($data->lineCoverage() as $file => $_data) {
            foreach ($_data as $line => $flag) {
                if ($flag === 1 && !isset($allowedLines[$file][$line])) {
                    $unintentionallyCoveredUnits[] = $this->wizard->lookup($file, $line);
                }
            }
        }

        $unintentionallyCoveredUnits = $this->processUnintentionallyCoveredUnits($unintentionallyCoveredUnits);

        if (!empty($unintentionallyCoveredUnits)) {
            throw new UnintentionallyCoveredCodeException(
                $unintentionallyCoveredUnits
            );
        }
    }

    private function getAllowedLines(array $linesToBeCovered, array $linesToBeUsed): array
    {
        $allowedLines = [];

        foreach (array_keys($linesToBeCovered) as $file) {
            if (!isset($allowedLines[$file])) {
                $allowedLines[$file] = [];
            }

            $allowedLines[$file] = array_merge(
                $allowedLines[$file],
                $linesToBeCovered[$file]
            );
        }

        foreach (array_keys($linesToBeUsed) as $file) {
            if (!isset($allowedLines[$file])) {
                $allowedLines[$file] = [];
            }

            $allowedLines[$file] = array_merge(
                $allowedLines[$file],
                $linesToBeUsed[$file]
            );
        }

        foreach (array_keys($allowedLines) as $file) {
            $allowedLines[$file] = array_flip(
                array_unique($allowedLines[$file])
            );
        }

        return $allowedLines;
    }

    /**
     * @param list<string> $unintentionallyCoveredUnits
     *
     * @throws ReflectionException
     *
     * @return list<string>
     */
    private function processUnintentionallyCoveredUnits(array $unintentionallyCoveredUnits): array
    {
        $unintentionallyCoveredUnits = array_unique($unintentionallyCoveredUnits);
        sort($unintentionallyCoveredUnits);

        foreach (array_keys($unintentionallyCoveredUnits) as $k => $v) {
            $unit = explode('::', $unintentionallyCoveredUnits[$k]);

            if (count($unit) !== 2) {
                continue;
            }

            try {
                $class = new ReflectionClass($unit[0]);

                foreach ($this->parentClassesExcludedFromUnintentionallyCoveredCodeCheck as $parentClass) {
                    if ($class->isSubclassOf($parentClass)) {
                        unset($unintentionallyCoveredUnits[$k]);

                        break;
                    }
                }
            } catch (\ReflectionException $e) {
                throw new ReflectionException(
                    $e->getMessage(),
                    (int) $e->getCode(),
                    $e
                );
            }
        }

        return array_values($unintentionallyCoveredUnits);
    }

    private function coveredFileAnalyser(): CoveredFileAnalyser
    {
        if ($this->coveredFileAnalyser !== null) {
            return $this->coveredFileAnalyser;
        }

        $this->coveredFileAnalyser = new ParsingCoveredFileAnalyser(
            $this->useAnnotationsForIgnoringCode,
            $this->ignoreDeprecatedCode
        );

        if ($this->cachesStaticAnalysis()) {
            $this->coveredFileAnalyser = new CachingCoveredFileAnalyser(
                $this->cacheDirectory,
                $this->coveredFileAnalyser
            );
        }

        return $this->coveredFileAnalyser;
    }

    private function uncoveredFileAnalyser(): UncoveredFileAnalyser
    {
        if ($this->uncoveredFileAnalyser !== null) {
            return $this->uncoveredFileAnalyser;
        }

        $this->uncoveredFileAnalyser = new ParsingUncoveredFileAnalyser;

        if ($this->cachesStaticAnalysis()) {
            $this->uncoveredFileAnalyser = new CachingUncoveredFileAnalyser(
                $this->cacheDirectory,
                $this->uncoveredFileAnalyser
            );
        }

        return $this->uncoveredFileAnalyser;
    }
}
