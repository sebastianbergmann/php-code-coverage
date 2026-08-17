<?php declare(strict_types=1);
/*
 * This file is part of phpunit/php-code-coverage.
 *
 * (c) Sebastian Bergmann <sebastian@phpunit.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace SebastianBergmann\CodeCoverage\Report\Jsonl;

use const DIRECTORY_SEPARATOR;
use const JSON_PRETTY_PRINT;
use const JSON_UNESCAPED_SLASHES;
use const JSON_UNESCAPED_UNICODE;
use const SORT_STRING;
use function array_any;
use function count;
use function fclose;
use function fopen;
use function fwrite;
use function is_dir;
use function is_file;
use function is_writable;
use function ksort;
use function str_replace;
use function str_starts_with;
use function strcmp;
use function strlen;
use function substr;
use function usort;
use DateTimeImmutable;
use DateTimeInterface;
use Generator;
use SebastianBergmann\CodeCoverage\Data\ProcessedBranchCoverageData;
use SebastianBergmann\CodeCoverage\Data\ProcessedCodeCoverageData;
use SebastianBergmann\CodeCoverage\Data\ProcessedFunctionCoverageData;
use SebastianBergmann\CodeCoverage\JsonException;
use SebastianBergmann\CodeCoverage\Node\Directory as DirectoryNode;
use SebastianBergmann\CodeCoverage\Node\File as FileNode;
use SebastianBergmann\CodeCoverage\PathExistsButIsNotDirectoryException;
use SebastianBergmann\CodeCoverage\Util\EnsuresUtf8;
use SebastianBergmann\CodeCoverage\Util\Filesystem;
use SebastianBergmann\CodeCoverage\Util\Json;
use SebastianBergmann\CodeCoverage\Version;
use SebastianBergmann\CodeCoverage\WriteOperationFailedException;

/**
 * @internal This class is not covered by the backward compatibility promise for phpunit/php-code-coverage
 *
 * @no-named-arguments Parameter names are not covered by the backward compatibility promise for phpunit/php-code-coverage
 *
 * @phpstan-import-type TestIndexType from ProcessedCodeCoverageData
 */
final class Facade
{
    use EnsuresUtf8;

    /**
     * Incremented only when a change to the format breaks a consumer that
     * relies on this version. Adding an optional key does not break one.
     */
    private const int SCHEMA_VERSION = 1;

    /**
     * @param non-empty-string $target
     *
     * @throws JsonException
     * @throws PathExistsButIsNotDirectoryException
     * @throws WriteOperationFailedException
     */
    public function process(DirectoryNode $report, string $target, ?DateTimeImmutable $generatedAt = null): void
    {
        if ($generatedAt === null) {
            $generatedAt = new DateTimeImmutable;
        }

        $this->initTargetDirectory($target);

        $sourceRoot = $report->pathAsString();
        $files      = $this->files($report, $sourceRoot);

        $this->writeRecords($target . DIRECTORY_SEPARATOR . 'coverage.jsonl', $this->coverageRecords($files));
        $this->writeRecords($target . DIRECTORY_SEPARATOR . 'tests.jsonl', $this->testRecords($files));
        $this->writeMeta($target . DIRECTORY_SEPARATOR . 'meta.json', $report, $sourceRoot, $files, $generatedAt);
    }

    /**
     * @param array<string, FileNode> $files
     */
    private function collectedBranchCoverage(array $files): bool
    {
        return array_any($files, static fn (FileNode $file) => $file->hasBranchCoverageData());
    }

    /**
     * @return array<string, FileNode>
     */
    private function files(DirectoryNode $report, string $sourceRoot): array
    {
        $files = [];

        foreach ($report as $node) {
            if (!$node instanceof FileNode) {
                continue;
            }

            if ($node->numberOfExecutableLines() === 0) {
                continue;
            }

            $files[$this->relativePath($node->pathAsString(), $sourceRoot)] = $node;
        }

        ksort($files, SORT_STRING);

        return $files;
    }

    /**
     * @param non-empty-string               $target
     * @param iterable<array<string, mixed>> $records
     *
     * @throws JsonException
     * @throws WriteOperationFailedException
     */
    private function writeRecords(string $target, iterable $records): void
    {
        $stream = @fopen($target, 'wb');

        if ($stream === false) {
            throw new WriteOperationFailedException($target);
        }

        foreach ($records as $record) {
            if (@fwrite($stream, $this->encode($record) . "\n") === false) {
                // @codeCoverageIgnoreStart
                fclose($stream);

                throw new WriteOperationFailedException($target);
                // @codeCoverageIgnoreEnd
            }
        }

        fclose($stream);
    }

    /**
     * @param array<string, FileNode> $files
     *
     * @return Generator<int, array<string, mixed>>
     */
    private function coverageRecords(array $files): Generator
    {
        foreach ($files as $path => $file) {
            yield $this->fileRecord($file, $path);
        }
    }

    /**
     * @param array<string, FileNode> $files
     *
     * @return Generator<int, array<string, mixed>>
     */
    private function testRecords(array $files): Generator
    {
        foreach ($this->coveredLinesByTest($files) as $test => $covers) {
            ksort($covers, SORT_STRING);

            yield [
                'test'   => $this->ensureUtf8($test),
                'covers' => $covers,
            ];
        }
    }

    /**
     * @param array<string, FileNode> $files
     *
     * @return array<non-empty-string, array<string, list<non-empty-string>>>
     */
    private function coveredLinesByTest(array $files): array
    {
        $coveredLinesByTest = [];

        foreach ($files as $path => $file) {
            $testData = $file->testData();
            $lines    = [];

            foreach ($file->lineCoverageData() as $line => $tests) {
                if ($tests === null) {
                    continue;
                }

                foreach ($tests as $testIndex => $hits) {
                    $lines[$testIndex][] = $line;
                }
            }

            foreach ($lines as $testIndex => $linesOfTest) {
                if (!isset($testData[$testIndex])) {
                    // @codeCoverageIgnoreStart
                    continue;
                    // @codeCoverageIgnoreEnd
                }

                $coveredLinesByTest[$testData[$testIndex]['name']][$path] = RangeEncoder::encode($linesOfTest);
            }
        }

        ksort($coveredLinesByTest, SORT_STRING);

        return $coveredLinesByTest;
    }

    /**
     * @param non-empty-string        $target
     * @param array<string, FileNode> $files
     *
     * @throws JsonException
     * @throws WriteOperationFailedException
     */
    private function writeMeta(string $target, DirectoryNode $report, string $sourceRoot, array $files, DateTimeImmutable $generatedAt): void
    {
        $meta = [
            'schemaVersion'   => self::SCHEMA_VERSION,
            'generator'       => 'php-code-coverage ' . Version::id(),
            'generatedAt'     => $generatedAt->format(DateTimeInterface::ATOM),
            'sourceRoot'      => $this->ensureUtf8(str_replace(DIRECTORY_SEPARATOR, '/', $sourceRoot)),
            'branchCoverage'  => $this->collectedBranchCoverage($files),
            'files'           => count($files),
            'executableLines' => $report->numberOfExecutableLines(),
            'executedLines'   => $report->numberOfExecutedLines(),
        ];

        Filesystem::write($target, Json::encode($meta, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) . "\n");
    }

    /**
     * @return array<string, mixed>
     */
    private function fileRecord(FileNode $file, string $path): array
    {
        $lineCoverage = $file->lineCoverageData();

        $record = [
            'file'       => $this->ensureUtf8($path),
            'executable' => $file->numberOfExecutableLines(),
            'executed'   => $file->numberOfExecutedLines(),
        ];

        $uncovered = RangeEncoder::encode($this->uncoveredLines($lineCoverage));

        if ($uncovered !== []) {
            $record['uncovered'] = $uncovered;
        }

        $symbols = $this->symbols($file, $lineCoverage);

        if ($symbols !== []) {
            $record['symbols'] = $symbols;
        }

        return $record;
    }

    /**
     * @param array<positive-int, ?array<TestIndexType, positive-int>> $lineCoverage
     *
     * @return list<positive-int>
     */
    private function uncoveredLines(array $lineCoverage): array
    {
        $lines = [];

        foreach ($lineCoverage as $line => $tests) {
            if ($tests === []) {
                $lines[] = $line;
            }
        }

        return $lines;
    }

    /**
     * @param array<positive-int, ?array<TestIndexType, positive-int>> $lineCoverage
     *
     * @return list<array<string, mixed>>
     */
    private function symbols(FileNode $file, array $lineCoverage): array
    {
        $functionCoverage = $file->functionCoverageData();
        $symbols          = [];

        foreach ($file->classesAndTraits() as $name => $classOrTrait) {
            foreach ($classOrTrait->methods as $method) {
                $key = $name . '->' . $method->methodName;

                $symbols[] = [
                    'name'      => $name . '::' . $method->methodName,
                    'startLine' => $method->startLine,
                    'endLine'   => $method->endLine,
                    'branches'  => $this->branchesOf($file, $functionCoverage, $key),
                ];
            }
        }

        foreach ($file->functions() as $name => $function) {
            $symbols[] = [
                'name'      => $name,
                'startLine' => $function->startLine,
                'endLine'   => $function->endLine,
                'branches'  => $this->branchesOf($file, $functionCoverage, $name),
            ];
        }

        usort(
            $symbols,
            static function (array $a, array $b): int
            {
                if ($a['startLine'] !== $b['startLine']) {
                    return $a['startLine'] <=> $b['startLine'];
                }

                return strcmp($a['name'], $b['name']);
            },
        );

        $records = [];

        foreach ($symbols as $symbol) {
            $record = $this->symbolRecord($symbol['name'], $symbol['startLine'], $symbol['endLine'], $lineCoverage, $symbol['branches']);

            if ($record === null) {
                continue;
            }

            $records[] = $record;
        }

        return $records;
    }

    /**
     * @param array<positive-int, ?array<TestIndexType, positive-int>> $lineCoverage
     * @param ?array<int, ProcessedBranchCoverageData>                 $branches     null when the driver collected no branch coverage
     *
     * @return ?array<string, mixed>
     */
    private function symbolRecord(string $name, int $startLine, int $endLine, array $lineCoverage, ?array $branches): ?array
    {
        $executableLines = 0;
        $uncoveredLines  = [];

        for ($line = $startLine; $line <= $endLine; $line++) {
            $tests = $lineCoverage[$line] ?? null;

            if ($tests === null) {
                continue;
            }

            $executableLines++;

            if ($tests === []) {
                $uncoveredLines[] = $line;
            }
        }

        if ($executableLines === 0) {
            return null;
        }

        $branchCoverage = null;

        if ($branches !== null && count($branches) > 1) {
            $branchCoverage = BranchCoverage::fromBranches($branches);
        }

        if (count($uncoveredLines) === $executableLines) {
            $state = 'uncovered';
        } elseif ($uncoveredLines !== []) {
            $state = 'partial';
        } elseif ($branchCoverage !== null && $branchCoverage->uncoveredLines !== []) {
            // Every line was executed, but control flow never went one of the
            // ways it can go. "Line 63 was executed, but only the true branch"
            // is something a test case can be derived from; "line 63 is
            // covered" is not
            $state = 'partial';
        } else {
            $state = 'covered';
        }

        $record = [
            'name'  => $this->ensureUtf8($name),
            'lines' => RangeEncoder::encodeBounds($startLine, $endLine),
            'state' => $state,
        ];

        if ($state === 'partial' && $uncoveredLines !== []) {
            $record['uncovered'] = RangeEncoder::encode($uncoveredLines);
        }

        if ($branchCoverage !== null) {
            $record['branches'] = [
                'executed' => $branchCoverage->executed,
                'total'    => $branchCoverage->total,
            ];

            if ($state !== 'uncovered' && $branchCoverage->uncoveredLines !== []) {
                $record['branches']['uncovered'] = RangeEncoder::encode($branchCoverage->uncoveredLines);
            }
        }

        return $record;
    }

    /**
     * @param array<non-empty-string, ProcessedFunctionCoverageData> $functionCoverage
     *
     * @return ?array<int, ProcessedBranchCoverageData>
     */
    private function branchesOf(FileNode $file, array $functionCoverage, string $key): ?array
    {
        if (!$file->hasBranchCoverageData()) {
            return null;
        }

        if (!isset($functionCoverage[$key])) {
            return [];
        }

        return $functionCoverage[$key]->branches;
    }

    private function relativePath(string $path, string $sourceRoot): string
    {
        $prefix = $sourceRoot . DIRECTORY_SEPARATOR;

        if (str_starts_with($path, $prefix)) {
            $path = substr($path, strlen($prefix));
        }

        return str_replace(DIRECTORY_SEPARATOR, '/', $path);
    }

    /**
     * @param array<string, mixed> $record
     *
     * @throws JsonException
     */
    private function encode(array $record): string
    {
        return Json::encode($record, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    }

    /**
     * @throws PathExistsButIsNotDirectoryException
     * @throws WriteOperationFailedException
     */
    private function initTargetDirectory(string $directory): void
    {
        if (is_file($directory)) {
            // @codeCoverageIgnoreStart
            if (!is_dir($directory)) {
                throw new PathExistsButIsNotDirectoryException($directory);
            }

            if (!is_writable($directory)) {
                throw new WriteOperationFailedException($directory);
            }
            // @codeCoverageIgnoreEnd
        }

        Filesystem::createDirectory($directory);
    }
}
