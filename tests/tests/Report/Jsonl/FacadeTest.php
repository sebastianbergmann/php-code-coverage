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
use function rmdir;
use function rtrim;
use function unlink;
use DateTimeImmutable;
use FilesystemIterator;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Medium;
use SebastianBergmann\CodeCoverage\Node\Directory as DirectoryNode;
use SebastianBergmann\CodeCoverage\TestCase;
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
