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

use function array_keys;
use function realpath;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use SebastianBergmann\CodeCoverage\Data\ProcessedCodeCoverageData;
use SebastianBergmann\CodeCoverage\Data\RawCodeCoverageData;
use SebastianBergmann\CodeCoverage\StaticAnalysis\AnalysisResult;
use SebastianBergmann\CodeCoverage\StaticAnalysis\FileAnalyser;
use SebastianBergmann\CodeCoverage\StaticAnalysis\LinesOfCode;
use SebastianBergmann\CodeCoverage\StaticAnalysis\ParsingSourceAnalyser;
use SebastianBergmann\CodeCoverage\StaticAnalysis\SourceAnalyser;
use SebastianBergmann\CodeCoverage\Test\Target\Mapper;
use SebastianBergmann\CodeCoverage\Test\TestSize;

#[CoversClass(FilterProcessor::class)]
final class FilterProcessorTest extends TestCase
{
    private FilterProcessor $processor;

    protected function setUp(): void
    {
        $this->processor = new FilterProcessor;
    }

    public function testApplyFilterRemovesExcludedFiles(): void
    {
        $data = RawCodeCoverageData::fromXdebugWithoutPathCoverage([
            'included.php' => [1 => 1],
            'excluded.php' => [1 => 1],
        ]);

        $filter = new Filter;
        $filter->includeFile(__FILE__);

        $this->processor->applyFilter($data, $filter);

        $this->assertArrayNotHasKey('excluded.php', $data->lineCoverage());
        $this->assertArrayNotHasKey('included.php', $data->lineCoverage());
    }

    public function testApplyFilterKeepsIncludedFiles(): void
    {
        $file = realpath(__FILE__);

        $data = RawCodeCoverageData::fromXdebugWithoutPathCoverage([
            $file => [1 => 1],
        ]);

        $filter = new Filter;
        $filter->includeFile($file);

        $this->processor->applyFilter($data, $filter);

        $this->assertArrayHasKey($file, $data->lineCoverage());
    }

    public function testApplyFilterSkipsFilteringWhenFilterIsEmpty(): void
    {
        $data = RawCodeCoverageData::fromXdebugWithoutPathCoverage([
            'any_file.php' => [1 => 1],
        ]);

        $filter = new Filter;

        $this->processor->applyFilter($data, $filter);

        $this->assertArrayHasKey('any_file.php', $data->lineCoverage());
    }

    public function testApplyExecutableLinesFilterKeepsOnlyExecutableLines(): void
    {
        $file = realpath(__FILE__);

        $data = RawCodeCoverageData::fromXdebugWithoutPathCoverage([
            $file => [1 => -1, 2 => 1, 3 => -1],
        ]);

        $filter = new Filter;
        $filter->includeFile($file);

        $analyser = $this->createFileAnalyser(
            new AnalysisResult([], [], [], [], new LinesOfCode(0, 0, 0), [2 => 2], []),
        );

        $this->processor->applyExecutableLinesFilter($data, $filter, $analyser);

        $this->assertSame([2], array_keys($data->lineCoverage()[$file]));
    }

    public function testApplyExecutableLinesFilterSkipsNonFilterFiles(): void
    {
        $data = RawCodeCoverageData::fromXdebugWithoutPathCoverage([
            'not_a_real_file.php' => [1 => 1, 2 => 1],
        ]);

        $filter = new Filter;

        $sourceAnalyser = $this->createMock(SourceAnalyser::class);
        $sourceAnalyser->expects($this->never())->method('analyse');

        $analyser = new FileAnalyser($sourceAnalyser, false, false);

        $this->processor->applyExecutableLinesFilter($data, $filter, $analyser);

        $this->assertSame([1 => 1, 2 => 1], $data->lineCoverage()['not_a_real_file.php']);
    }

    public function testApplyIgnoredLinesFilterRemovesIgnoredLines(): void
    {
        $file = realpath(__FILE__);

        $data = RawCodeCoverageData::fromXdebugWithoutPathCoverage([
            $file => [1 => 1, 2 => 1, 3 => 1],
        ]);

        $filter = new Filter;
        $filter->includeFile($file);

        $analyser = $this->createFileAnalyser(
            new AnalysisResult([], [], [], [], new LinesOfCode(0, 0, 0), [], [2 => 2]),
        );

        $this->processor->applyIgnoredLinesFilter($data, $filter, $analyser);

        $this->assertSame([1, 3], array_keys($data->lineCoverage()[$file]));
    }

    public function testApplyIgnoredLinesFilterSkipsNonFilterFiles(): void
    {
        $data = RawCodeCoverageData::fromXdebugWithoutPathCoverage([
            'not_a_real_file.php' => [1 => 1, 2 => 1],
        ]);

        $filter = new Filter;

        $sourceAnalyser = $this->createMock(SourceAnalyser::class);
        $sourceAnalyser->expects($this->never())->method('analyse');

        $analyser = new FileAnalyser($sourceAnalyser, false, false);

        $this->processor->applyIgnoredLinesFilter($data, $filter, $analyser);

        $this->assertSame([1 => 1, 2 => 1], $data->lineCoverage()['not_a_real_file.php']);
    }

    public function testApplyCoversAndUsesFilterClearsDataWhenLinesToBeCoveredIsFalse(): void
    {
        $data = RawCodeCoverageData::fromXdebugWithoutPathCoverage([
            'file.php' => [1 => 1],
        ]);

        $this->processor->applyCoversAndUsesFilter(
            $data,
            false,
            [],
            TestSize::Small,
            false,
            new Mapper($this->emptyMap()),
            [],
        );

        $this->assertSame([], $data->lineCoverage());
    }

    public function testApplyCoversAndUsesFilterReturnsEarlyWhenLinesToBeCoveredIsEmpty(): void
    {
        $data = RawCodeCoverageData::fromXdebugWithoutPathCoverage([
            'file.php' => [1 => 1],
        ]);

        $this->processor->applyCoversAndUsesFilter(
            $data,
            [],
            [],
            TestSize::Small,
            false,
            new Mapper($this->emptyMap()),
            [],
        );

        $this->assertSame([1 => 1], $data->lineCoverage()['file.php']);
    }

    public function testApplyCoversAndUsesFilterKeepsOnlyCoveredLines(): void
    {
        $data = RawCodeCoverageData::fromXdebugWithoutPathCoverage([
            'file.php' => [1 => 1, 2 => 1, 3 => 1],
        ]);

        $this->processor->applyCoversAndUsesFilter(
            $data,
            ['file.php' => [1, 2]],
            [],
            TestSize::Small,
            false,
            new Mapper($this->emptyMap()),
            [],
        );

        $this->assertSame([1, 2], array_keys($data->lineCoverage()['file.php']));
    }

    public function testApplyCoversAndUsesFilterRemovesFilesNotInCoversList(): void
    {
        $data = RawCodeCoverageData::fromXdebugWithoutPathCoverage([
            'covered.php'   => [1 => 1],
            'uncovered.php' => [1 => 1],
        ]);

        $this->processor->applyCoversAndUsesFilter(
            $data,
            ['covered.php' => [1]],
            [],
            TestSize::Small,
            false,
            new Mapper($this->emptyMap()),
            [],
        );

        $this->assertArrayHasKey('covered.php', $data->lineCoverage());
        $this->assertArrayNotHasKey('uncovered.php', $data->lineCoverage());
    }

    public function testApplyCoversAndUsesFilterRunsUnintentionallyCoveredCodeCheckForSmallTests(): void
    {
        $data = RawCodeCoverageData::fromXdebugWithoutPathCoverage([
            'file.php' => [1 => 1, 5 => 1],
        ]);

        $this->expectException(UnintentionallyCoveredCodeException::class);

        $this->processor->applyCoversAndUsesFilter(
            $data,
            ['file.php' => [1]],
            [],
            TestSize::Small,
            true,
            new Mapper($this->emptyMap()),
            [],
        );
    }

    public function testApplyCoversAndUsesFilterSkipsUnintentionallyCoveredCodeCheckForMediumTests(): void
    {
        $data = RawCodeCoverageData::fromXdebugWithoutPathCoverage([
            'file.php' => [1 => 1, 5 => 1],
        ]);

        $this->processor->applyCoversAndUsesFilter(
            $data,
            ['file.php' => [1]],
            [],
            TestSize::Medium,
            true,
            new Mapper($this->emptyMap()),
            [],
        );

        $this->assertSame([1 => 1], $data->lineCoverage()['file.php']);
    }

    public function testApplyCoversAndUsesFilterSkipsUnintentionallyCoveredCodeCheckForLargeTests(): void
    {
        $data = RawCodeCoverageData::fromXdebugWithoutPathCoverage([
            'file.php' => [1 => 1, 5 => 1],
        ]);

        $this->processor->applyCoversAndUsesFilter(
            $data,
            ['file.php' => [1]],
            [],
            TestSize::Large,
            true,
            new Mapper($this->emptyMap()),
            [],
        );

        $this->assertSame([1 => 1], $data->lineCoverage()['file.php']);
    }

    public function testApplyCoversAndUsesFilterSkipsUnintentionallyCoveredCodeCheckWhenDisabled(): void
    {
        $data = RawCodeCoverageData::fromXdebugWithoutPathCoverage([
            'file.php' => [1 => 1, 5 => 1],
        ]);

        $this->processor->applyCoversAndUsesFilter(
            $data,
            ['file.php' => [1]],
            [],
            TestSize::Small,
            false,
            new Mapper($this->emptyMap()),
            [],
        );

        $this->assertSame([1 => 1], $data->lineCoverage()['file.php']);
    }

    public function testUncoveredFilesFromFilterReturnsDataForUncoveredFiles(): void
    {
        $file = realpath(__DIR__ . '/../_files/BankAccount.php');

        $filter = new Filter;
        $filter->includeFile($file);

        $data     = new ProcessedCodeCoverageData;
        $analyser = new FileAnalyser(new ParsingSourceAnalyser, false, false);

        $result = $this->processor->uncoveredFilesFromFilter($filter, $data, $analyser);

        $this->assertCount(1, $result);
        $this->assertArrayHasKey($file, $result[0]->lineCoverage());
    }

    public function testUncoveredFilesFromFilterReturnsEmptyWhenAllFilesAreCovered(): void
    {
        $file = realpath(__DIR__ . '/../_files/BankAccount.php');

        $filter = new Filter;
        $filter->includeFile($file);

        $data     = new ProcessedCodeCoverageData;
        $analyser = new FileAnalyser(new ParsingSourceAnalyser, false, false);

        $data->initializeUnseenData(
            RawCodeCoverageData::fromXdebugWithoutPathCoverage([
                $file => [1 => -1],
            ]),
        );

        $result = $this->processor->uncoveredFilesFromFilter($filter, $data, $analyser);

        $this->assertSame([], $result);
    }

    public function testUncoveredFilesFromFilterReturnsEmptyWhenFilterIsEmpty(): void
    {
        $filter   = new Filter;
        $data     = new ProcessedCodeCoverageData;
        $analyser = new FileAnalyser(new ParsingSourceAnalyser, false, false);

        $result = $this->processor->uncoveredFilesFromFilter($filter, $data, $analyser);

        $this->assertSame([], $result);
    }

    public function testUncoveredFilesFromFilterSkipsNonExistentFiles(): void
    {
        $realFile = realpath(__DIR__ . '/../_files/BankAccount.php');

        $filter = new Filter;
        $filter->includeFile($realFile);

        $data     = new ProcessedCodeCoverageData;
        $analyser = new FileAnalyser(new ParsingSourceAnalyser, false, false);

        // Mark the real file as covered so only the non-existent file remains uncovered
        $data->initializeUnseenData(
            RawCodeCoverageData::fromXdebugWithoutPathCoverage([
                $realFile => [1 => -1],
            ]),
        );

        $result = $this->processor->uncoveredFilesFromFilter($filter, $data, $analyser);

        $this->assertSame([], $result);
    }

    public function testUncoveredFilesFromFilterReturnsMultipleFiles(): void
    {
        $file1 = realpath(__DIR__ . '/../_files/BankAccount.php');
        $file2 = realpath(__DIR__ . '/../_files/CoveredClass.php');

        $filter = new Filter;
        $filter->includeFile($file1);
        $filter->includeFile($file2);

        $data     = new ProcessedCodeCoverageData;
        $analyser = new FileAnalyser(new ParsingSourceAnalyser, false, false);

        $result = $this->processor->uncoveredFilesFromFilter($filter, $data, $analyser);

        $this->assertCount(2, $result);
    }

    /**
     * @return array{namespaces: array<empty>, traits: array<empty>, classes: array<empty>, classesThatExtendClass: array<empty>, classesThatImplementInterface: array<empty>, methods: array<empty>, functions: array<empty>, reverseLookup: array<empty>}
     */
    private function emptyMap(): array
    {
        return [
            'namespaces'                    => [],
            'traits'                        => [],
            'classes'                       => [],
            'classesThatExtendClass'        => [],
            'classesThatImplementInterface' => [],
            'methods'                       => [],
            'functions'                     => [],
            'reverseLookup'                 => [],
        ];
    }

    private function createFileAnalyser(AnalysisResult $result): FileAnalyser
    {
        $sourceAnalyser = $this->createStub(SourceAnalyser::class);
        $sourceAnalyser->method('analyse')->willReturn($result);

        return new FileAnalyser($sourceAnalyser, false, false);
    }
}
