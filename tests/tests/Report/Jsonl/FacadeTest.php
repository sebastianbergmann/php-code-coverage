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
use const JSON_THROW_ON_ERROR;
use function explode;
use function file_get_contents;
use function iterator_count;
use function json_decode;
use function mkdir;
use function rmdir;
use function rtrim;
use function unlink;
use DateTimeImmutable;
use FilesystemIterator;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Medium;
use SebastianBergmann\CodeCoverage\Data\ProcessedBranchCoverageData;
use SebastianBergmann\CodeCoverage\Data\ProcessedFunctionCoverageData;
use SebastianBergmann\CodeCoverage\Node\Directory as DirectoryNode;
use SebastianBergmann\CodeCoverage\Node\File as FileNode;
use SebastianBergmann\CodeCoverage\StaticAnalysis\Function_;
use SebastianBergmann\CodeCoverage\StaticAnalysis\LinesOfCode;
use SebastianBergmann\CodeCoverage\TestCase;
use SebastianBergmann\CodeCoverage\WriteOperationFailedException;
use SplFileInfo;

#[CoversClass(Facade::class)]
#[Medium]
final class FacadeTest extends TestCase
{
    private string $expectedFilesPath;

    protected function setUp(): void
    {
        parent::setUp();

        $this->expectedFilesPath = TEST_FILES_PATH . 'Report' . DIRECTORY_SEPARATOR . 'Jsonl';
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        $this->removeDirectoryContents(TEST_FILES_PATH . 'tmp');
    }

    public function testLineCoverageForBankAccountTest(): void
    {
        $this->assertFilesEquals(
            $this->expectedFilesPath . DIRECTORY_SEPARATOR . 'CoverageForBankAccount',
            $this->process($this->getLineCoverageForBankAccount()->getReport()),
        );
    }

    public function testPathCoverageForBankAccountTest(): void
    {
        $this->assertFilesEquals(
            $this->expectedFilesPath . DIRECTORY_SEPARATOR . 'PathCoverageForBankAccount',
            $this->process($this->getPathCoverageForBankAccount()->getReport()),
        );
    }

    public function testCoverageForFileWithIgnoredLines(): void
    {
        $this->assertFilesEquals(
            $this->expectedFilesPath . DIRECTORY_SEPARATOR . 'CoverageForFileWithIgnoredLines',
            $this->process($this->getCoverageForFileWithIgnoredLines()->getReport()),
        );
    }

    public function testCoverageForClassWithAnonymousFunction(): void
    {
        $this->assertFilesEquals(
            $this->expectedFilesPath . DIRECTORY_SEPARATOR . 'CoverageForClassWithAnonymousFunction',
            $this->process($this->getCoverageForClassWithAnonymousFunction()->getReport()),
        );
    }

    public function testProducesByteIdenticalOutputForTwoRunsOverTheSameData(): void
    {
        $report      = $this->getLineCoverageForBankAccount()->getReport();
        $generatedAt = new DateTimeImmutable('2026-08-16T09:12:44+00:00');

        $first  = $this->process($report, 'first', $generatedAt);
        $second = $this->process($report, 'second', $generatedAt);

        foreach (['meta.json', 'coverage.jsonl', 'tests.jsonl'] as $file) {
            $this->assertSame(
                file_get_contents($first . DIRECTORY_SEPARATOR . $file),
                file_get_contents($second . DIRECTORY_SEPARATOR . $file),
                "{$file} is not byte-identical across two runs",
            );
        }
    }

    public function testEveryLineOfEveryJsonlFileIsValidJsonOnItsOwn(): void
    {
        $target = $this->process($this->getPathCoverageForBankAccount()->getReport());

        foreach (['coverage.jsonl', 'tests.jsonl'] as $file) {
            $lines = $this->linesOf($target . DIRECTORY_SEPARATOR . $file);

            $this->assertNotSame([], $lines);

            foreach ($lines as $line) {
                $this->assertIsArray(json_decode($line, true, 512, JSON_THROW_ON_ERROR));
            }
        }
    }

    /**
     * Every branch of a symbol that never ran is uncovered, so listing them
     * only restates the state of the symbol. The counts are kept because they
     * say how much there is to cover, which the state does not.
     */
    public function testDoesNotListUncoveredBranchesOfASymbolThatNeverRan(): void
    {
        $target = $this->process($this->getPathCoverageForBankAccount()->getReport());

        $contents = file_get_contents($target . DIRECTORY_SEPARATOR . 'coverage.jsonl');

        $this->assertNotFalse($contents);

        $this->assertStringContainsString(
            '{"name":"BankAccount::setBalance","lines":"11-18","state":"uncovered","branches":{"executed":0,"total":4}}',
            $contents,
        );
    }

    /**
     * A percentage is an invitation to optimise the number instead of the
     * gap, so the format deliberately does not contain one anywhere.
     */
    public function testDoesNotReportPercentages(): void
    {
        $target = $this->process($this->getPathCoverageForBankAccount()->getReport());

        foreach (['meta.json', 'coverage.jsonl', 'tests.jsonl'] as $file) {
            $contents = file_get_contents($target . DIRECTORY_SEPARATOR . $file);

            $this->assertNotFalse($contents);
            $this->assertStringNotContainsStringIgnoringCase('percent', $contents);
        }
    }

    public function testReportsNeitherDirectoriesNorFilesWithoutExecutableLines(): void
    {
        $root      = new DirectoryNode('/path/to/src');
        $directory = $root->addDirectory('directory');

        $root->addFile($this->file('WithoutExecutableLines.php', $root, [1 => null]));
        $directory->addFile($this->file('WithExecutableLines.php', $directory, [1 => [0 => 1]]));

        $this->assertSame(
            [
                ['file' => 'directory/WithExecutableLines.php', 'executable' => 1, 'executed' => 1],
            ],
            $this->coverageRecords($root),
        );
    }

    public function testListsUncoveredLinesOfSymbolOfWhichOnlySomeLinesWereExecuted(): void
    {
        $report = $this->reportForFileWithFunctions(
            [2 => [0 => 1], 3 => [], 4 => [0 => 1], 5 => []],
            ['f' => new Function_('f', 'f', '', 1, 6, 'function f(): void', 2)],
        );

        $this->assertSame(
            [
                ['name' => 'f', 'lines' => '1-6', 'state' => 'partial', 'uncovered' => ['3', '5']],
            ],
            $this->symbolRecords($report),
        );
    }

    /**
     * Every line of the function was executed, but the edge out of the
     * decision on line 2 that leads to the branch on line 4 was never taken.
     */
    public function testListsUncoveredBranchesOfSymbolOfWhichEveryLineWasExecuted(): void
    {
        $report = $this->reportForFileWithFunctions(
            [2 => [0 => 1], 3 => [0 => 1]],
            ['f' => new Function_('f', 'f', '', 1, 5, 'function f(): void', 2)],
            [
                'f' => new ProcessedFunctionCoverageData(
                    [
                        0 => new ProcessedBranchCoverageData(0, 2, 2, 2, [0 => 1], [0 => 1, 1 => 2], [0 => 1, 1 => 0]),
                        1 => new ProcessedBranchCoverageData(3, 4, 3, 3, [0 => 1], [], []),
                        2 => new ProcessedBranchCoverageData(5, 6, 4, 4, [], [], []),
                    ],
                    [],
                ),
            ],
        );

        $this->assertSame(
            [
                [
                    'name'     => 'f',
                    'lines'    => '1-5',
                    'state'    => 'partial',
                    'branches' => ['executed' => 2, 'total' => 3, 'uncovered' => ['2', '4']],
                ],
            ],
            $this->symbolRecords($report),
        );
    }

    public function testDoesNotReportBranchesOfSymbolForWhichNoBranchCoverageDataIsAvailable(): void
    {
        $report = $this->reportForFileWithFunctions(
            [2 => [0 => 1]],
            ['f' => new Function_('f', 'f', '', 1, 3, 'function f(): void', 1)],
            [],
            true,
        );

        $this->assertSame(
            [
                ['name' => 'f', 'lines' => '1-3', 'state' => 'covered'],
            ],
            $this->symbolRecords($report),
        );
    }

    public function testSortsSymbolsThatStartOnTheSameLineByName(): void
    {
        $report = $this->reportForFileWithFunctions(
            [1 => [0 => 1], 2 => [0 => 1]],
            [
                'c' => new Function_('c', 'c', '', 2, 2, 'function c(): void', 1),
                'b' => new Function_('b', 'b', '', 1, 1, 'function b(): void', 1),
                'a' => new Function_('a', 'a', '', 1, 1, 'function a(): void', 1),
            ],
        );

        $this->assertSame(
            [
                ['name' => 'a', 'lines' => '1', 'state' => 'covered'],
                ['name' => 'b', 'lines' => '1', 'state' => 'covered'],
                ['name' => 'c', 'lines' => '2', 'state' => 'covered'],
            ],
            $this->symbolRecords($report),
        );
    }

    public function testCannotWriteRecordsToFileThatCannotBeOpened(): void
    {
        $target = TEST_FILES_PATH . 'tmp' . DIRECTORY_SEPARATOR . 'report';

        mkdir($target . DIRECTORY_SEPARATOR . 'coverage.jsonl', 0o777, true);

        $this->expectException(WriteOperationFailedException::class);
        $this->expectExceptionMessageIs('Cannot write to "' . $target . DIRECTORY_SEPARATOR . 'coverage.jsonl"');

        (new Facade)->process($this->getLineCoverageForBankAccount()->getReport(), $target);
    }

    private function process(DirectoryNode $report, string $directory = 'report', ?DateTimeImmutable $generatedAt = null): string
    {
        $target = TEST_FILES_PATH . 'tmp' . DIRECTORY_SEPARATOR . $directory;

        (new Facade)->process($report, $target, $generatedAt);

        return $target;
    }

    /**
     * @return list<string>
     */
    private function linesOf(string $file): array
    {
        $contents = file_get_contents($file);

        $this->assertNotFalse($contents);

        return explode("\n", rtrim($contents, "\n"));
    }

    /**
     * @return list<mixed>
     */
    private function coverageRecords(DirectoryNode $report): array
    {
        $records = [];

        foreach ($this->linesOf($this->process($report) . DIRECTORY_SEPARATOR . 'coverage.jsonl') as $line) {
            $records[] = json_decode($line, true, 512, JSON_THROW_ON_ERROR);
        }

        return $records;
    }

    /**
     * @return mixed the records for the symbols of the only file of the report
     */
    private function symbolRecords(DirectoryNode $report): mixed
    {
        $records = $this->coverageRecords($report);

        $this->assertCount(1, $records);
        $this->assertIsArray($records[0]);
        $this->assertArrayHasKey('symbols', $records[0]);

        return $records[0]['symbols'];
    }

    /**
     * @param array<positive-int, ?array<non-negative-int, positive-int>> $lineCoverage
     * @param array<string, Function_>                                    $functions
     * @param array<non-empty-string, ProcessedFunctionCoverageData>      $functionCoverage
     */
    private function reportForFileWithFunctions(array $lineCoverage, array $functions, array $functionCoverage = [], ?bool $hasBranchCoverageData = null): DirectoryNode
    {
        $root = new DirectoryNode('/path/to/src');

        $root->addFile(
            $this->file(
                'functions.php',
                $root,
                $lineCoverage,
                $functions,
                $functionCoverage,
                $hasBranchCoverageData ?? $functionCoverage !== [],
            ),
        );

        return $root;
    }

    /**
     * @param non-empty-string                                            $name
     * @param array<positive-int, ?array<non-negative-int, positive-int>> $lineCoverage
     * @param array<string, Function_>                                    $functions
     * @param array<non-empty-string, ProcessedFunctionCoverageData>      $functionCoverage
     */
    private function file(string $name, DirectoryNode $parent, array $lineCoverage, array $functions = [], array $functionCoverage = [], bool $hasBranchCoverageData = false): FileNode
    {
        return new FileNode(
            $name,
            $parent,
            'abc123',
            $lineCoverage,
            $functionCoverage,
            [0 => ['name' => 'ExampleTest::testOne', 'size' => 'small', 'status' => 'passed', 'time' => 0.0]],
            [],
            [],
            $functions,
            new LinesOfCode(10, 0, 10),
            $hasBranchCoverageData,
        );
    }

    private function assertFilesEquals(string $expectedFilesPath, string $actualFilesPath): void
    {
        $expectedFilesIterator = new FilesystemIterator($expectedFilesPath);
        $actualFilesIterator   = new FilesystemIterator($actualFilesPath);

        $this->assertSame(
            iterator_count($expectedFilesIterator),
            iterator_count($actualFilesIterator),
            'Generated files and expected files not match',
        );

        foreach ($expectedFilesIterator as $fileInfo) {
            if (!$fileInfo instanceof SplFileInfo) {
                continue; // @codeCoverageIgnore
            }

            $filename = $fileInfo->getFilename();

            $actualFile = $actualFilesPath . DIRECTORY_SEPARATOR . $filename;

            $this->assertFileExists($actualFile);

            $actual = file_get_contents($actualFile);

            $this->assertNotFalse($actual);

            $this->assertStringMatchesFormatFile(
                $fileInfo->getPathname(),
                $actual,
                "{$filename} not match",
            );
        }
    }

    private function removeDirectoryContents(string $path): void
    {
        foreach (new FilesystemIterator($path) as $fileInfo) {
            if (!$fileInfo instanceof SplFileInfo) {
                continue; // @codeCoverageIgnore
            }

            if ($fileInfo->isDir()) {
                $this->removeDirectoryContents($fileInfo->getPathname());

                rmdir($fileInfo->getPathname());

                continue;
            }

            unlink($fileInfo->getPathname());
        }
    }
}
