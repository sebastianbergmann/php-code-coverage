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
use PHPUnit\Util\Test;
use SebastianBergmann\CodeCoverage\Driver\Driver;
use SebastianBergmann\CodeCoverage\Driver\PcovDriver;
use SebastianBergmann\CodeCoverage\Driver\PhpdbgDriver;
use SebastianBergmann\CodeCoverage\Driver\XdebugDriver;
use SebastianBergmann\CodeCoverage\Node\Builder;
use SebastianBergmann\CodeCoverage\Node\Directory;
use SebastianBergmann\CodeUnitReverseLookup\Wizard;
use SebastianBergmann\Environment\Runtime;

/**
 * Provides collection functionality for PHP code coverage information.
 */
final class CodeCoverage
{
    private const UNCOVERED_FILES_FROM_WHITELIST = 'UNCOVERED_FILES_FROM_WHITELIST';

    /**
     * @var Driver
     */
    private $driver;

    /**
     * @var Filter
     */
    private $filter;

    /**
     * @var Wizard
     */
    private $wizard;

    /**
     * @var bool
     */
    private $cacheTokens = false;

    /**
     * @var bool
     */
    private $checkForUnintentionallyCoveredCode = false;

    /**
     * @var bool
     */
    private $forceCoversAnnotation = false;

    /**
     * @var bool
     */
    private $checkForUnexecutedCoveredCode = false;

    /**
     * @var bool
     */
    private $checkForMissingCoversAnnotation = false;

    /**
     * @var bool
     */
    private $addUncoveredFilesFromWhitelist = true;

    /**
     * @var bool
     */
    private $processUncoveredFilesFromWhitelist = false;

    /**
     * @var bool
     */
    private $ignoreDeprecatedCode = false;

    /**
     * @var PhptTestCase|string|TestCase
     */
    private $currentId;

    /**
     * Code coverage data.
     *
     * @var ProcessedCodeCoverageData
     */
    private $data;

    /**
     * @var array
     */
    private $ignoredLines = [];

    /**
     * @var bool
     */
    private $disableIgnoredLines = false;

    /**
     * Test data.
     *
     * @var array
     */
    private $tests = [];

    /**
     * @var string[]
     */
    private $unintentionallyCoveredSubclassesWhitelist = [];

    /**
     * Determine if the data has been initialized or not
     *
     * @var bool
     */
    private $isInitialized = false;

    /**
     * @var Directory
     */
    private $report;

    /**
     * @throws RuntimeException
     */
    public function __construct(Driver $driver = null, Filter $filter = null)
    {
        if ($filter === null) {
            $filter = new Filter;
        }

        if ($driver === null) {
            $driver = $this->selectDriver($filter);
        }

        $this->driver = $driver;
        $this->filter = $filter;

        $this->wizard = new Wizard;
        $this->data   = new ProcessedCodeCoverageData;
    }

    /**
     * Returns the code coverage information as a graph of node objects.
     */
    public function getReport(): Directory
    {
        if ($this->report === null) {
            $this->report = (new Builder)->build($this);
        }

        return $this->report;
    }

    /**
     * Clears collected code coverage data.
     */
    public function clear(): void
    {
        $this->isInitialized = false;
        $this->currentId     = null;
        $this->data          = new ProcessedCodeCoverageData();
        $this->tests         = [];
        $this->report        = null;
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
        if (!$raw && $this->addUncoveredFilesFromWhitelist) {
            $this->addUncoveredFilesFromWhitelist();
        }

        return $this->data;
    }

    /**
     * Sets the coverage data.
     */
    public function setData(ProcessedCodeCoverageData $data): void
    {
        $this->data   = $data;
        $this->report = null;
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

    /**
     * Start collection of code coverage information.
     *
     * @param PhptTestCase|string|TestCase $id
     *
     * @throws RuntimeException
     */
    public function start($id, bool $clear = false): void
    {
        if ($clear) {
            $this->clear();
        }

        if ($this->isInitialized === false) {
            $this->initializeData();
        }

        $this->currentId = $id;

        $this->driver->start();
    }

    /**
     * Stop collection of code coverage information.
     *
     * @param array|false $linesToBeCovered
     *
     * @throws MissingCoversAnnotationException
     * @throws CoveredCodeNotExecutedException
     * @throws RuntimeException
     * @throws \ReflectionException
     */
    public function stop(bool $append = true, $linesToBeCovered = [], array $linesToBeUsed = [], bool $ignoreForceCoversAnnotation = false): RawCodeCoverageData
    {
        if (!\is_array($linesToBeCovered) && $linesToBeCovered !== false) {
            throw new InvalidArgumentException(
                '$linesToBeCovered must be an array or false'
            );
        }

        $data = $this->driver->stop();
        $this->append($data, null, $append, $linesToBeCovered, $linesToBeUsed, $ignoreForceCoversAnnotation);

        $this->currentId = null;

        return $data;
    }

    /**
     * Appends code coverage data.
     *
     * @param PhptTestCase|string|TestCase $id
     * @param array|false                  $linesToBeCovered
     *
     * @throws \SebastianBergmann\CodeCoverage\UnintentionallyCoveredCodeException
     * @throws \SebastianBergmann\CodeCoverage\MissingCoversAnnotationException
     * @throws \SebastianBergmann\CodeCoverage\CoveredCodeNotExecutedException
     * @throws \ReflectionException
     * @throws RuntimeException
     */
    public function append(RawCodeCoverageData $rawData, $id = null, bool $append = true, $linesToBeCovered = [], array $linesToBeUsed = [], bool $ignoreForceCoversAnnotation = false): void
    {
        if ($id === null) {
            $id = $this->currentId;
        }

        if ($id === null) {
            throw new RuntimeException;
        }

        $this->applyWhitelistFilter($rawData);
        $this->applyIgnoredLinesFilter($rawData);

        $this->data->initializeUnseenData($rawData);

        if (!$append) {
            return;
        }

        if ($id !== self::UNCOVERED_FILES_FROM_WHITELIST) {
            $this->applyCoversAnnotationFilter(
                $rawData,
                $linesToBeCovered,
                $linesToBeUsed,
                $ignoreForceCoversAnnotation
            );

            if (empty($rawData->lineCoverage())) {
                return;
            }

            $size   = 'unknown';
            $status = -1;

            if ($id instanceof TestCase) {
                $_size = $id->getSize();

                if ($_size === Test::SMALL) {
                    $size = 'small';
                } elseif ($_size === Test::MEDIUM) {
                    $size = 'medium';
                } elseif ($_size === Test::LARGE) {
                    $size = 'large';
                }

                $status = $id->getStatus();
                $id     = \get_class($id) . '::' . $id->getName();
            } elseif ($id instanceof PhptTestCase) {
                $size = 'large';
                $id   = $id->getName();
            }

            $this->tests[$id] = ['size' => $size, 'status' => $status];

            $this->data->markCodeAsExecutedByTestCase($id, $rawData);
        }

        $this->report = null;
    }

    /**
     * Merges the data from another instance.
     *
     * @param CodeCoverage $that
     */
    public function merge(self $that): void
    {
        $this->filter->setWhitelistedFiles(
            \array_merge($this->filter->getWhitelistedFiles(), $that->filter()->getWhitelistedFiles())
        );

        $this->data->merge($that->data);

        $this->tests  = \array_merge($this->tests, $that->getTests());
        $this->report = null;
    }

    public function setCacheTokens(bool $flag): void
    {
        $this->cacheTokens = $flag;
    }

    public function getCacheTokens(): bool
    {
        return $this->cacheTokens;
    }

    public function setCheckForUnintentionallyCoveredCode(bool $flag): void
    {
        $this->checkForUnintentionallyCoveredCode = $flag;
    }

    public function setForceCoversAnnotation(bool $flag): void
    {
        $this->forceCoversAnnotation = $flag;
    }

    public function setCheckForMissingCoversAnnotation(bool $flag): void
    {
        $this->checkForMissingCoversAnnotation = $flag;
    }

    public function setCheckForUnexecutedCoveredCode(bool $flag): void
    {
        $this->checkForUnexecutedCoveredCode = $flag;
    }

    public function setAddUncoveredFilesFromWhitelist(bool $flag): void
    {
        $this->addUncoveredFilesFromWhitelist = $flag;
    }

    public function setProcessUncoveredFilesFromWhitelist(bool $flag): void
    {
        $this->processUncoveredFilesFromWhitelist = $flag;
    }

    public function setDisableIgnoredLines(bool $flag): void
    {
        $this->disableIgnoredLines = $flag;
    }

    public function setIgnoreDeprecatedCode(bool $flag): void
    {
        $this->ignoreDeprecatedCode = $flag;
    }

    public function setUnintentionallyCoveredSubclassesWhitelist(array $whitelist): void
    {
        $this->unintentionallyCoveredSubclassesWhitelist = $whitelist;
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
     * Applies the @covers annotation filtering.
     *
     * @param array|false $linesToBeCovered
     *
     * @throws \SebastianBergmann\CodeCoverage\CoveredCodeNotExecutedException
     * @throws \ReflectionException
     * @throws MissingCoversAnnotationException
     * @throws UnintentionallyCoveredCodeException
     */
    private function applyCoversAnnotationFilter(RawCodeCoverageData $rawData, $linesToBeCovered, array $linesToBeUsed, bool $ignoreForceCoversAnnotation): void
    {
        if ($linesToBeCovered === false ||
            ($this->forceCoversAnnotation && empty($linesToBeCovered) && !$ignoreForceCoversAnnotation)) {
            if ($this->checkForMissingCoversAnnotation) {
                throw new MissingCoversAnnotationException;
            }

            $rawData->clear();

            return;
        }

        if (empty($linesToBeCovered)) {
            return;
        }

        if ($this->checkForUnintentionallyCoveredCode &&
            (!$this->currentId instanceof TestCase ||
            (!$this->currentId->isMedium() && !$this->currentId->isLarge()))) {
            $this->performUnintentionallyCoveredCodeCheck($rawData, $linesToBeCovered, $linesToBeUsed);
        }

        if ($this->checkForUnexecutedCoveredCode) {
            $this->performUnexecutedCoveredCodeCheck($rawData, $linesToBeCovered, $linesToBeUsed);
        }

        $rawLineData         = $rawData->lineCoverage();
        $filesWithNoCoverage = \array_diff_key($rawLineData, $linesToBeCovered);

        foreach (\array_keys($filesWithNoCoverage) as $fileWithNoCoverage) {
            $rawData->removeCoverageDataForFile($fileWithNoCoverage);
        }

        if (\is_array($linesToBeCovered)) {
            foreach ($linesToBeCovered as $fileToBeCovered => $includedLines) {
                $rawData->keepCoverageDataOnlyForLines($fileToBeCovered, $includedLines);
            }
        }
    }

    private function applyWhitelistFilter(RawCodeCoverageData $data): void
    {
        foreach (\array_keys($data->lineCoverage()) as $filename) {
            if ($this->filter->isFiltered($filename)) {
                $data->removeCoverageDataForFile($filename);
            }
        }
    }

    private function applyIgnoredLinesFilter(RawCodeCoverageData $data): void
    {
        foreach (\array_keys($data->lineCoverage()) as $filename) {
            $data->removeCoverageDataForLines($filename, $this->getLinesToBeIgnored($filename));
        }
    }

    /**
     * @throws CoveredCodeNotExecutedException
     * @throws MissingCoversAnnotationException
     * @throws RuntimeException
     * @throws UnintentionallyCoveredCodeException
     * @throws \ReflectionException
     */
    private function addUncoveredFilesFromWhitelist(): void
    {
        $uncoveredFiles = \array_diff(
            $this->filter->getWhitelist(),
            \array_keys($this->data->lineCoverage())
        );

        foreach ($uncoveredFiles as $uncoveredFile) {
            if (\file_exists($uncoveredFile)) {
                if ($this->cacheTokens) {
                    $tokens = \PHP_Token_Stream_CachingFactory::get($uncoveredFile);
                } else {
                    $tokens = new \PHP_Token_Stream($uncoveredFile);
                }

                $this->append(RawCodeCoverageData::fromUncoveredFile($uncoveredFile, $tokens), self::UNCOVERED_FILES_FROM_WHITELIST);
            }
        }
    }

    private function getLinesToBeIgnored(string $fileName): array
    {
        if (isset($this->ignoredLines[$fileName])) {
            return $this->ignoredLines[$fileName];
        }

        try {
            return $this->getLinesToBeIgnoredInner($fileName);
        } catch (\OutOfBoundsException $e) {
            // This can happen with PHP_Token_Stream if the file is syntactically invalid,
            // and probably affects a file that wasn't executed.
            return [];
        }
    }

    private function getLinesToBeIgnoredInner(string $fileName): array
    {
        $this->ignoredLines[$fileName] = [];

        $lines = \file($fileName);

        if ($this->cacheTokens) {
            $tokens = \PHP_Token_Stream_CachingFactory::get($fileName);
        } else {
            $tokens = new \PHP_Token_Stream($fileName);
        }

        if ($this->disableIgnoredLines) {
            $this->ignoredLines[$fileName] = \array_unique($this->ignoredLines[$fileName]);
            \sort($this->ignoredLines[$fileName]);

            return $this->ignoredLines[$fileName];
        }

        $ignore = false;
        $stop   = false;

        foreach ($tokens->tokens() as $token) {
            switch (\get_class($token)) {
                case \PHP_Token_COMMENT::class:
                case \PHP_Token_DOC_COMMENT::class:
                    $_token = \trim((string) $token);
                    $_line  = \trim($lines[$token->getLine() - 1]);

                    if ($_token === '// @codeCoverageIgnore' ||
                        $_token === '//@codeCoverageIgnore') {
                        $ignore = true;
                        $stop   = true;
                    } elseif ($_token === '// @codeCoverageIgnoreStart' ||
                        $_token === '//@codeCoverageIgnoreStart') {
                        $ignore = true;
                    } elseif ($_token === '// @codeCoverageIgnoreEnd' ||
                        $_token === '//@codeCoverageIgnoreEnd') {
                        $stop = true;
                    }

                    break;

                 // Intentional fallthrough

                case \PHP_Token_INTERFACE::class:
                case \PHP_Token_TRAIT::class:
                case \PHP_Token_CLASS::class:
                case \PHP_Token_FUNCTION::class:
                    /* @var \PHP_Token_Interface $token */

                    $docblock = (string) $token->getDocblock();

                    if (\strpos($docblock, '@codeCoverageIgnore') || ($this->ignoreDeprecatedCode && \strpos($docblock, '@deprecated'))) {
                        $endLine = $token->getEndLine();

                        for ($i = $token->getLine(); $i <= $endLine; $i++) {
                            $this->ignoredLines[$fileName][] = $i;
                        }
                    }

                    break;
            }

            if ($ignore) {
                $this->ignoredLines[$fileName][] = $token->getLine();

                if ($stop) {
                    $ignore = false;
                    $stop   = false;
                }
            }
        }

        $this->ignoredLines[$fileName] = \array_unique(
            $this->ignoredLines[$fileName]
        );

        $this->ignoredLines[$fileName] = \array_unique($this->ignoredLines[$fileName]);
        \sort($this->ignoredLines[$fileName]);

        return $this->ignoredLines[$fileName];
    }

    /**
     * @throws \ReflectionException
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

    /**
     * @throws CoveredCodeNotExecutedException
     */
    private function performUnexecutedCoveredCodeCheck(RawCodeCoverageData $rawData, array $linesToBeCovered, array $linesToBeUsed): void
    {
        $executedCodeUnits = $this->coverageToCodeUnits($rawData);
        $message           = '';

        foreach ($this->linesToCodeUnits($linesToBeCovered) as $codeUnit) {
            if (!\in_array($codeUnit, $executedCodeUnits)) {
                $message .= \sprintf(
                    '- %s is expected to be executed (@covers) but was not executed' . "\n",
                    $codeUnit
                );
            }
        }

        foreach ($this->linesToCodeUnits($linesToBeUsed) as $codeUnit) {
            if (!\in_array($codeUnit, $executedCodeUnits)) {
                $message .= \sprintf(
                    '- %s is expected to be executed (@uses) but was not executed' . "\n",
                    $codeUnit
                );
            }
        }

        if (!empty($message)) {
            throw new CoveredCodeNotExecutedException($message);
        }
    }

    private function getAllowedLines(array $linesToBeCovered, array $linesToBeUsed): array
    {
        $allowedLines = [];

        foreach (\array_keys($linesToBeCovered) as $file) {
            if (!isset($allowedLines[$file])) {
                $allowedLines[$file] = [];
            }

            $allowedLines[$file] = \array_merge(
                $allowedLines[$file],
                $linesToBeCovered[$file]
            );
        }

        foreach (\array_keys($linesToBeUsed) as $file) {
            if (!isset($allowedLines[$file])) {
                $allowedLines[$file] = [];
            }

            $allowedLines[$file] = \array_merge(
                $allowedLines[$file],
                $linesToBeUsed[$file]
            );
        }

        foreach (\array_keys($allowedLines) as $file) {
            $allowedLines[$file] = \array_flip(
                \array_unique($allowedLines[$file])
            );
        }

        return $allowedLines;
    }

    /**
     * @throws RuntimeException
     */
    private function selectDriver(Filter $filter): Driver
    {
        $runtime = new Runtime;

        if ($runtime->hasPHPDBGCodeCoverage()) {
            return new PhpdbgDriver;
        }

        if ($runtime->hasPCOV()) {
            return new PcovDriver($filter);
        }

        if ($runtime->hasXdebug()) {
            return new XdebugDriver($filter);
        }

        throw new RuntimeException('No code coverage driver available');
    }

    private function processUnintentionallyCoveredUnits(array $unintentionallyCoveredUnits): array
    {
        $unintentionallyCoveredUnits = \array_unique($unintentionallyCoveredUnits);
        \sort($unintentionallyCoveredUnits);

        foreach (\array_keys($unintentionallyCoveredUnits) as $k => $v) {
            $unit = \explode('::', $unintentionallyCoveredUnits[$k]);

            if (\count($unit) !== 2) {
                continue;
            }

            $class = new \ReflectionClass($unit[0]);

            foreach ($this->unintentionallyCoveredSubclassesWhitelist as $whitelisted) {
                if ($class->isSubclassOf($whitelisted)) {
                    unset($unintentionallyCoveredUnits[$k]);

                    break;
                }
            }
        }

        return \array_values($unintentionallyCoveredUnits);
    }

    /**
     * @throws CoveredCodeNotExecutedException
     * @throws MissingCoversAnnotationException
     * @throws RuntimeException
     * @throws UnintentionallyCoveredCodeException
     * @throws \ReflectionException
     */
    private function initializeData(): void
    {
        $this->isInitialized = true;

        if ($this->processUncoveredFilesFromWhitelist) {
            // by collecting dead code data here on an initial pass, future runs with test data do not need to
            if ($this->driver->canDetectDeadCode()) {
                $this->driver->enableDeadCodeDetection();
            }

            $this->driver->start();

            foreach ($this->filter->getWhitelist() as $file) {
                if ($this->filter->isFile($file)) {
                    include_once $file;
                }
            }

            // having now collected dead code for the entire whitelist, we can safely skip this data on subsequent runs
            if ($this->driver->canDetectDeadCode()) {
                $this->driver->disableDeadCodeDetection();
            }

            $this->append($this->driver->stop(), self::UNCOVERED_FILES_FROM_WHITELIST);
        }
    }

    private function coverageToCodeUnits(RawCodeCoverageData $rawData): array
    {
        $codeUnits = [];

        foreach ($rawData->lineCoverage() as $filename => $lines) {
            foreach ($lines as $line => $flag) {
                if ($flag === 1) {
                    $codeUnits[] = $this->wizard->lookup($filename, $line);
                }
            }
        }

        return \array_unique($codeUnits);
    }

    private function linesToCodeUnits(array $data): array
    {
        $codeUnits = [];

        foreach ($data as $filename => $lines) {
            foreach ($lines as $line) {
                $codeUnits[] = $this->wizard->lookup($filename, $line);
            }
        }

        return \array_unique($codeUnits);
    }
}
