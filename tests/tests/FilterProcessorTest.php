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
use function ksort;
use function realpath;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RequiresPhp;
use PHPUnit\Framework\Attributes\Small;
use PHPUnit\Framework\Attributes\Ticket;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use SebastianBergmann\CodeCoverage\Data\ProcessedCodeCoverageData;
use SebastianBergmann\CodeCoverage\Data\RawCodeCoverageData;
use SebastianBergmann\CodeCoverage\StaticAnalysis\AnalysisResult;
use SebastianBergmann\CodeCoverage\StaticAnalysis\FileAnalyser;
use SebastianBergmann\CodeCoverage\StaticAnalysis\LinesOfCode;
use SebastianBergmann\CodeCoverage\StaticAnalysis\ParsingSourceAnalyser;
use SebastianBergmann\CodeCoverage\StaticAnalysis\SourceAnalyser;
use SebastianBergmann\CodeCoverage\Test\Target\Mapper;
use SebastianBergmann\CodeCoverage\Test\Target\TargetCollection;
use SebastianBergmann\CodeCoverage\Test\TestSize;

/**
 * @phpstan-import-type TargetMap from Mapper
 */
#[CoversClass(FilterProcessor::class)]
#[Small]
final class FilterProcessorTest extends TestCase
{
    private FilterProcessor $processor;

    protected function setUp(): void
    {
        $this->processor = new FilterProcessor;
    }

    #[Ticket('https://bugs.xdebug.org/view.php?id=2438')]
    public function testRemovesDataForKnownFileOfWhichNoLineWasExecuted(): void
    {
        $data = RawCodeCoverageData::fromLineCoverage(['known.php' => [8 => -1, 13 => -1]]);

        $this->processor->removeDataForKnownFilesThatWereNotExecuted($data, $this->dataFor('known.php'));

        $this->assertSame([], $data->lineCoverage());
    }

    public function testDoesNotRemoveDataForKnownFileOfWhichALineWasExecuted(): void
    {
        $data = RawCodeCoverageData::fromLineCoverage(['known.php' => [8 => 1, 13 => -1]]);

        $this->processor->removeDataForKnownFilesThatWereNotExecuted($data, $this->dataFor('known.php'));

        $this->assertSame(['known.php' => [8 => 1, 13 => -1]], $data->lineCoverage());
    }

    public function testDoesNotRemoveDataForFileThatIsNotKnown(): void
    {
        $data = RawCodeCoverageData::fromLineCoverage(['unknown.php' => [8 => -1, 13 => -1]]);

        $this->processor->removeDataForKnownFilesThatWereNotExecuted($data, $this->dataFor('known.php'));

        $this->assertSame(['unknown.php' => [8 => -1, 13 => -1]], $data->lineCoverage());
    }

    public function testDoesNotRemoveDataForKnownFileWithBranchAndPathCoverageData(): void
    {
        $data = RawCodeCoverageData::fromLineAndBranchCoverage(
            ['known.php' => [8 => -1, 13 => -1]],
            ['known.php' => ['foo' => ['branches' => [], 'paths' => []]]],
        );

        $this->processor->removeDataForKnownFilesThatWereNotExecuted($data, $this->dataFor('known.php'));

        $this->assertSame(['known.php' => [8 => -1, 13 => -1]], $data->lineCoverage());
    }

    public function testApplyFilterRemovesExcludedFiles(): void
    {
        $data = RawCodeCoverageData::fromLineCoverage([
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
        $file = self::realpath(__FILE__);

        $data = RawCodeCoverageData::fromLineCoverage([
            $file => [1 => 1],
        ]);

        $filter = new Filter;
        $filter->includeFile($file);

        $this->processor->applyFilter($data, $filter);

        $this->assertArrayHasKey($file, $data->lineCoverage());
    }

    public function testApplyFilterSkipsFilteringWhenFilterIsEmpty(): void
    {
        $data = RawCodeCoverageData::fromLineCoverage([
            'any_file.php' => [1 => 1],
        ]);

        $filter = new Filter;

        $this->processor->applyFilter($data, $filter);

        $this->assertArrayHasKey('any_file.php', $data->lineCoverage());
    }

    public function testApplyExecutableLinesFilterKeepsOnlyExecutableLines(): void
    {
        $file = self::realpath(__FILE__);

        $data = RawCodeCoverageData::fromLineCoverage([
            $file => [1 => -1, 2 => 1, 3 => -1],
        ]);

        $filter = new Filter;
        $filter->includeFile($file);

        $analyser = $this->createFileAnalyser(
            new AnalysisResult([], [], [], [], new LinesOfCode(0, 0, 0), [2 => 2], [], [], []),
        );

        $this->processor->applyExecutableLinesFilter($data, $filter, $analyser);

        $lineCoverage = $data->lineCoverage();

        $this->assertArrayHasKey($file, $lineCoverage);
        $this->assertSame([2], array_keys($lineCoverage[$file]));
    }

    #[Ticket('https://github.com/sebastianbergmann/php-code-coverage/issues/1305')]
    public function testApplyExecutableLinesFilterDoesNotMarkTernaryArmsInArrayLiteralAsNotExecuted(): void
    {
        $file = self::realpath(__DIR__ . '/../_files/source_ternary_in_array_literal.php');

        // Line coverage as reported by Xdebug 3.5 after calling row(7) and
        // row(null): both arms of every operator were taken, yet lines 8
        // (": null,") and 11 (": [],") are missing because the engine folds
        // constant operands into the opcode that builds the array literal
        $data = RawCodeCoverageData::fromLineCoverage([
            $file => [6 => 1, 7 => 1, 10 => 1, 12 => 1, 13 => 1, 15 => -2],
        ]);

        $filter = new Filter;
        $filter->includeFile($file);

        $analyser = new FileAnalyser(new ParsingSourceAnalyser, true, true);

        $this->processor->applyExecutableLinesFilter($data, $filter, $analyser);

        $lineCoverage = $data->lineCoverage();

        $this->assertArrayHasKey($file, $lineCoverage);

        $linesForFile = $lineCoverage[$file];

        ksort($linesForFile);

        $this->assertSame(
            [
                5  => 1,
                6  => 1,
                7  => 1,
                8  => 1,
                9  => 1,
                10 => 1,
                11 => 1,
                12 => 1,
                13 => 1,
                14 => 1,
            ],
            $linesForFile,
        );
    }

    #[RequiresPhp('>=8.4.0')]
    #[Ticket('https://github.com/sebastianbergmann/php-code-coverage/issues/1305')]
    public function testApplyExecutableLinesFilterDoesNotMarkPropertyHookBodyAsNotExecutedWhenDriverDoesNotReportIt(): void
    {
        $file = self::realpath(__DIR__ . '/../_files/source_with_ternary_in_property_hook.php');

        // Line coverage as reported by PCOV 1.0.12, which collects no line of
        // a property hook body at all, after reading $label on an instance
        // created with and one created without a value
        $data = RawCodeCoverageData::fromLineCoverage([
            $file => [5 => 1],
        ]);

        $filter = new Filter;
        $filter->includeFile($file);

        $analyser = new FileAnalyser(new ParsingSourceAnalyser, true, true);

        $this->processor->applyExecutableLinesFilter($data, $filter, $analyser);

        $lineCoverage = $data->lineCoverage();

        $this->assertArrayHasKey($file, $lineCoverage);

        // Lines 9, 10 and 11 must not be reported as not executed: the driver
        // cannot see them, so nothing is known about them
        $this->assertSame([5 => 1], $lineCoverage[$file]);
    }

    #[RequiresPhp('>=8.4.0')]
    #[Ticket('https://github.com/sebastianbergmann/php-code-coverage/issues/1314')]
    public function testApplyExecutableLinesFilterDoesNotMarkUnexecutedLinesOfPropertyHookBodyAsExecuted(): void
    {
        $file = self::realpath(__DIR__ . '/../_files/source_with_branches_in_property_hooks.php');

        // Line coverage as reported by Xdebug 3.5 after assigning an even
        // value to $value: the else arm of the set hook (line 12) was not
        // executed and the get hook was not called at all
        $data = RawCodeCoverageData::fromLineCoverage([
            $file => [9 => 1, 10 => 1, 12 => -1, 14 => 1, 17 => 1],
        ]);

        $filter = new Filter;
        $filter->includeFile($file);

        $analyser = new FileAnalyser(new ParsingSourceAnalyser, true, true);

        $this->processor->applyExecutableLinesFilter($data, $filter, $analyser);

        $lineCoverage = $data->lineCoverage();

        $this->assertArrayHasKey($file, $lineCoverage);

        $linesForFile = $lineCoverage[$file];

        ksort($linesForFile);

        $this->assertSame([9 => 1, 10 => 1, 12 => -1], $linesForFile);
    }

    public function testApplyExecutableLinesFilterDoesNotMarkLineOfRightOperandOfBooleanAndThatWasNotExecutedAsExecuted(): void
    {
        $file = self::realpath(__DIR__ . '/../_files/source_boolean_and_on_multiple_lines.php');

        // Line coverage as reported by Xdebug 3.6 after every function was
        // called such that the left operand of the first && is false, and
        // condition() such that the left operand of || is true.
        //
        // Only the line of the last operand of the assignment (line 7) is
        // reported as not executed. The lines of the other right operands
        // (6, 16, 26, 36, 45, and 54) also hold an opcode that is executed
        // when the operand is not evaluated.
        $this->assertSame(
            [
                5  => 1,
                6  => 1,
                7  => -1,
                9  => 1,
                14 => 1,
                15 => 1,
                16 => 1,
                17 => -1,
                19 => 1,
                24 => 1,
                25 => 1,
                26 => 1,
                27 => -1,
                30 => 1,
                35 => 1,
                36 => 1,
                38 => 1,
                43 => 1,
                44 => 1,
                45 => 1,
                46 => 1,
                48 => 1,
                53 => 1,
                54 => 1,
                55 => 1,
                58 => -1,
            ],
            $this->lineCoverageAfterExecutableLinesFilter(
                $file,
                [
                    5  => 1,
                    6  => 1,
                    7  => -1,
                    9  => 1,
                    10 => -1,
                    14 => 1,
                    15 => 1,
                    16 => 1,
                    17 => -1,
                    19 => 1,
                    20 => -1,
                    24 => 1,
                    25 => 1,
                    26 => 1,
                    27 => -1,
                    30 => 1,
                    31 => -1,
                    35 => 1,
                    36 => 1,
                    38 => 1,
                    39 => -1,
                    43 => 1,
                    44 => 1,
                    45 => 1,
                    48 => 1,
                    49 => -1,
                    53 => 1,
                    54 => 1,
                    55 => 1,
                    58 => -1,
                    59 => -1,
                    60 => 1,
                ],
            ),
        );
    }

    public function testApplyExecutableLinesFilterDoesNotMarkLineOfRightOperandOfBooleanAndThatWasNotExecutedAsExecutedWhenOptimizerIsEnabled(): void
    {
        $file = self::realpath(__DIR__ . '/../_files/source_boolean_and_on_multiple_lines.php');

        // Line coverage as reported by PCOV 1.0.12 for the same calls while
        // the optimizer of OPcache is enabled: the lines of the right
        // operands in the assignment (6 and 7), in the argument of the
        // function call (45), and in the condition (54) are reported as not
        // executed
        $this->assertSame(
            [
                5  => 1,
                6  => -1,
                7  => -1,
                9  => 1,
                14 => 1,
                15 => 1,
                16 => 1,
                17 => -1,
                19 => 1,
                24 => 1,
                25 => 1,
                26 => 1,
                27 => -1,
                30 => 1,
                35 => 1,
                36 => 1,
                38 => 1,
                43 => 1,
                44 => 1,
                45 => -1,
                46 => 1,
                48 => 1,
                53 => 1,
                54 => -1,
                55 => 1,
                58 => -1,
            ],
            $this->lineCoverageAfterExecutableLinesFilter(
                $file,
                [
                    5  => 1,
                    6  => -1,
                    7  => -1,
                    9  => 1,
                    14 => 1,
                    15 => 1,
                    16 => 1,
                    17 => -1,
                    19 => 1,
                    24 => 1,
                    25 => 1,
                    26 => 1,
                    27 => -1,
                    30 => 1,
                    35 => 1,
                    36 => 1,
                    38 => 1,
                    43 => 1,
                    44 => 1,
                    45 => -1,
                    48 => 1,
                    53 => 1,
                    54 => -1,
                    55 => 1,
                    58 => -1,
                    60 => 1,
                ],
            ),
        );
    }

    public function testApplyExecutableLinesFilterDoesNotMarkOperandOfBooleanAndThatIsEvaluatedByCompilerAsNotExecuted(): void
    {
        $file = self::realpath(__DIR__ . '/../_files/source_boolean_and_with_operand_without_variable.php');

        // Line coverage as reported by Xdebug 3.6 after calling supportsSsl()
        // with an array and with null, and canFork(), while ext-curl is
        // loaded: line 5 is missing because the compiler evaluates
        // \defined('CURL_VERSION_SSL')
        $this->assertSame(
            [5 => 1, 6 => 1, 7 => 1, 12 => 1, 13 => 1, 14 => 1],
            $this->lineCoverageAfterExecutableLinesFilter(
                $file,
                [6 => 1, 7 => 1, 8 => -1, 12 => 1, 13 => 1, 14 => 1, 15 => -1, 16 => 1],
            ),
        );
    }

    public function testApplyExecutableLinesFilterDoesNotMarkOperandOfBooleanAndThatIsEvaluatedByOptimizerAsNotExecuted(): void
    {
        $file = self::realpath(__DIR__ . '/../_files/source_boolean_and_with_operand_without_variable.php');

        // Line coverage as reported by PCOV 1.0.12 for the same calls while
        // ext-pcntl and ext-posix are loaded and the optimizer of OPcache is
        // enabled: lines 12 and 13 are missing as well because the optimizer
        // evaluates \function_exists()
        $this->assertSame(
            [5 => 1, 6 => 1, 7 => 1, 12 => 1, 13 => 1, 14 => 1],
            $this->lineCoverageAfterExecutableLinesFilter(
                $file,
                [6 => 1, 7 => 1, 14 => 1, 16 => 1],
            ),
        );
    }

    public function testApplyExecutableLinesFilterDoesNotMarkOperandOfBooleanAndInExpressionThatIsEvaluatedBeforeItIsCompiledAsNotExecuted(): void
    {
        $file = self::realpath(__DIR__ . '/../_files/source_boolean_and_in_expression_evaluated_before_compilation.php');

        // Line coverage as reported by Xdebug 3.6 after every operand of
        // every && was evaluated, except for those in the closure, which was
        // not called: lines 7, 17, 26, 28, and 35 are missing because the
        // opcodes of the right operands of && are attributed to other lines
        $this->assertSame(
            [
                7  => 1,
                8  => 1,
                9  => 1,
                10 => 1,
                16 => 1,
                17 => 1,
                18 => 1,
                19 => 1,
                24 => 1,
                25 => 1,
                26 => 1,
                27 => 1,
                28 => 1,
                29 => 1,
                34 => 1,
                35 => 1,
                37 => 1,
                42 => 1,
                43 => 1,
                44 => 1,
                45 => -1,
                46 => -1,
                47 => 1,
                48 => 1,
            ],
            $this->lineCoverageAfterExecutableLinesFilter(
                $file,
                [
                    5  => 1,
                    8  => 1,
                    10 => 1,
                    12 => -1,
                    16 => 1,
                    18 => 1,
                    19 => 1,
                    20 => -1,
                    25 => 1,
                    27 => 1,
                    30 => -1,
                    34 => 1,
                    37 => 1,
                    38 => -1,
                    43 => 1,
                    45 => -1,
                    46 => -1,
                    47 => 1,
                    49 => -1,
                    50 => 1,
                ],
            ),
        );
    }

    public function testApplyExecutableLinesFilterDoesNotMarkUnexecutedTernaryArmInOperandOfBooleanAndInArrayLiteralAsExecuted(): void
    {
        $file = self::realpath(__DIR__ . '/../_files/source_ternary_in_operand_of_boolean_and_in_array_literal.php');

        // Line coverage as reported by Xdebug 3.6 after calling
        // row(true, false, 1): the first arm of the ternary operator (line 6)
        // was not executed
        $this->assertSame(
            [5 => 1, 6 => -1, 7 => 1],
            $this->lineCoverageAfterExecutableLinesFilter(
                $file,
                [5 => 1, 6 => -1, 7 => 1, 8 => -1, 9 => 1],
            ),
        );
    }

    public function testApplyExecutableLinesFilterDoesNotMarkLinesGroupedWithRightOperandOfBooleanAndAsNotExecuted(): void
    {
        $file = self::realpath(__DIR__ . '/../_files/source_boolean_and_with_right_operand_on_first_line_of_grouped_lines.php');

        // Line coverage as reported by Xdebug 3.6 after every operand of
        // every && was evaluated: the closing line of the body of the arrow
        // function (line 7) is reported as not executed, and the second line
        // of the first arm of the ternary operator (line 16) is missing
        $this->assertSame(
            [
                5  => 1,
                6  => 1,
                7  => 1,
                9  => 1,
                14 => 1,
                15 => 1,
                16 => 1,
                17 => 1,
                23 => 1,
                24 => 1,
                25 => 1,
                26 => 1,
            ],
            $this->lineCoverageAfterExecutableLinesFilter(
                $file,
                [
                    5  => 1,
                    6  => 1,
                    7  => -1,
                    9  => 1,
                    10 => -1,
                    14 => 1,
                    15 => 1,
                    17 => 1,
                    18 => -1,
                    23 => 1,
                    24 => 1,
                    26 => 1,
                    28 => -1,
                    29 => 1,
                ],
            ),
        );
    }

    #[Ticket('https://bugs.xdebug.org/view.php?id=2438')]
    public function testApplyExecutableLinesFilterDoesNotMarkLinesOfMethodThatWasNotReportedAsNotExecuted(): void
    {
        $file = self::realpath(__DIR__ . '/../_files/source_with_method_with_function_call_that_is_not_reported.php');

        // Line coverage as reported by Xdebug 3.6 after calling one() when
        // the class was compiled before the collection of code coverage data
        // was started: no line of two() is reported. Marking the line with
        // the function call (line 13) as not executed would keep the other
        // line of two() from being seeded from static analysis
        $this->assertSame(
            [8 => 1],
            $this->lineCoverageAfterExecutableLinesFilter($file, [8 => 1, 9 => -1]),
        );
    }

    public function testApplyExecutableLinesFilterMarksLinesOfMethodThatWasReportedAsNotExecuted(): void
    {
        $file = self::realpath(__DIR__ . '/../_files/source_with_method_with_function_call_that_is_not_reported.php');

        $this->assertSame(
            [8 => 1, 13 => -1, 15 => -1],
            $this->lineCoverageAfterExecutableLinesFilter($file, [8 => 1, 9 => -1, 15 => -1]),
        );
    }

    public function testApplyExecutableLinesFilterKeepsRawDataForFileThatCannotBeParsed(): void
    {
        $file = self::realpath(__DIR__ . '/../_files/source_that_cannot_be_parsed.php');

        $data = RawCodeCoverageData::fromLineCoverage([
            $file => [1 => -1, 3 => 1, 5 => 1],
        ]);

        $filter = new Filter;
        $filter->includeFile($file);

        $analyser = new FileAnalyser(new ParsingSourceAnalyser, true, true);

        $this->processor->applyExecutableLinesFilter($data, $filter, $analyser);

        $lineCoverage = $data->lineCoverage();

        $this->assertArrayHasKey($file, $lineCoverage);
        $this->assertSame([1 => -1, 3 => 1, 5 => 1], $lineCoverage[$file]);
    }

    public function testApplyExecutableLinesFilterSkipsNonFilterFiles(): void
    {
        $data = RawCodeCoverageData::fromLineCoverage([
            'not_a_real_file.php' => [1 => 1, 2 => 1],
        ]);

        $filter = new Filter;

        $sourceAnalyser = $this->createMock(SourceAnalyser::class);
        $sourceAnalyser->expects($this->never())->method('analyse');

        $analyser = new FileAnalyser($sourceAnalyser, false, false);

        $this->processor->applyExecutableLinesFilter($data, $filter, $analyser);

        $lineCoverage = $data->lineCoverage();

        $this->assertArrayHasKey('not_a_real_file.php', $lineCoverage);
        $this->assertSame([1 => 1, 2 => 1], $lineCoverage['not_a_real_file.php']);
    }

    public function testApplyIgnoredLinesFilterRemovesIgnoredLines(): void
    {
        $file = self::realpath(__FILE__);

        $data = RawCodeCoverageData::fromLineCoverage([
            $file => [1 => 1, 2 => 1, 3 => 1],
        ]);

        $filter = new Filter;
        $filter->includeFile($file);

        $analyser = $this->createFileAnalyser(
            new AnalysisResult([], [], [], [], new LinesOfCode(0, 0, 0), [], [], [], [2 => true]),
        );

        $this->processor->applyIgnoredLinesFilter($data, $filter, $analyser);

        $lineCoverage = $data->lineCoverage();

        $this->assertArrayHasKey($file, $lineCoverage);
        $this->assertSame([1, 3], array_keys($lineCoverage[$file]));
    }

    public function testApplyIgnoredLinesFilterSkipsNonFilterFiles(): void
    {
        $data = RawCodeCoverageData::fromLineCoverage([
            'not_a_real_file.php' => [1 => 1, 2 => 1],
        ]);

        $filter = new Filter;

        $sourceAnalyser = $this->createMock(SourceAnalyser::class);
        $sourceAnalyser->expects($this->never())->method('analyse');

        $analyser = new FileAnalyser($sourceAnalyser, false, false);

        $this->processor->applyIgnoredLinesFilter($data, $filter, $analyser);

        $lineCoverage = $data->lineCoverage();

        $this->assertArrayHasKey('not_a_real_file.php', $lineCoverage);
        $this->assertSame([1 => 1, 2 => 1], $lineCoverage['not_a_real_file.php']);
    }

    public function testApplyCoversAndUsesFilterClearsDataWhenLinesToBeCoveredIsFalse(): void
    {
        $data = RawCodeCoverageData::fromLineCoverage([
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
            $this->emptyTargetCollection(),
            $this->emptyTargetCollection(),
        );

        $this->assertSame([], $data->lineCoverage());
    }

    public function testApplyCoversAndUsesFilterReturnsEarlyWhenLinesToBeCoveredIsEmpty(): void
    {
        $data = RawCodeCoverageData::fromLineCoverage([
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
            $this->emptyTargetCollection(),
            $this->emptyTargetCollection(),
        );

        $lineCoverage = $data->lineCoverage();

        $this->assertArrayHasKey('file.php', $lineCoverage);
        $this->assertSame([1 => 1], $lineCoverage['file.php']);
    }

    public function testApplyCoversAndUsesFilterKeepsOnlyCoveredLines(): void
    {
        $data = RawCodeCoverageData::fromLineCoverage([
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
            $this->emptyTargetCollection(),
            $this->emptyTargetCollection(),
        );

        $lineCoverage = $data->lineCoverage();

        $this->assertArrayHasKey('file.php', $lineCoverage);
        $this->assertSame([1, 2], array_keys($lineCoverage['file.php']));
    }

    public function testApplyCoversAndUsesFilterRemovesFilesNotInCoversList(): void
    {
        $data = RawCodeCoverageData::fromLineCoverage([
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
            $this->emptyTargetCollection(),
            $this->emptyTargetCollection(),
        );

        $this->assertArrayHasKey('covered.php', $data->lineCoverage());
        $this->assertArrayNotHasKey('uncovered.php', $data->lineCoverage());
    }

    public function testApplyCoversAndUsesFilterRunsUnintentionallyCoveredCodeCheckForSmallTests(): void
    {
        $data = RawCodeCoverageData::fromLineCoverage([
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
            $this->emptyTargetCollection(),
            $this->emptyTargetCollection(),
        );
    }

    public function testApplyCoversAndUsesFilterSkipsUnintentionallyCoveredCodeCheckForMediumTests(): void
    {
        $data = RawCodeCoverageData::fromLineCoverage([
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
            $this->emptyTargetCollection(),
            $this->emptyTargetCollection(),
        );

        $lineCoverage = $data->lineCoverage();

        $this->assertArrayHasKey('file.php', $lineCoverage);
        $this->assertSame([1 => 1], $lineCoverage['file.php']);
    }

    public function testApplyCoversAndUsesFilterSkipsUnintentionallyCoveredCodeCheckForLargeTests(): void
    {
        $data = RawCodeCoverageData::fromLineCoverage([
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
            $this->emptyTargetCollection(),
            $this->emptyTargetCollection(),
        );

        $lineCoverage = $data->lineCoverage();

        $this->assertArrayHasKey('file.php', $lineCoverage);
        $this->assertSame([1 => 1], $lineCoverage['file.php']);
    }

    public function testApplyCoversAndUsesFilterSkipsUnintentionallyCoveredCodeCheckWhenDisabled(): void
    {
        $data = RawCodeCoverageData::fromLineCoverage([
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
            $this->emptyTargetCollection(),
            $this->emptyTargetCollection(),
        );

        $lineCoverage = $data->lineCoverage();

        $this->assertArrayHasKey('file.php', $lineCoverage);
        $this->assertSame([1 => 1], $lineCoverage['file.php']);
    }

    public function testUncoveredFilesFromFilterReturnsDataForUncoveredFiles(): void
    {
        $file = self::realpath(__DIR__ . '/../_files/BankAccount.php');

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
        $file = self::realpath(__DIR__ . '/../_files/BankAccount.php');

        $filter = new Filter;
        $filter->includeFile($file);

        $data     = new ProcessedCodeCoverageData;
        $analyser = new FileAnalyser(new ParsingSourceAnalyser, false, false);

        $data->initializeUnseenData(
            RawCodeCoverageData::fromLineCoverage([
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
        $realFile = self::realpath(__DIR__ . '/../_files/BankAccount.php');

        $filter = new Filter;
        $filter->includeFile($realFile);

        $data     = new ProcessedCodeCoverageData;
        $analyser = new FileAnalyser(new ParsingSourceAnalyser, false, false);

        // Mark the real file as covered so only the non-existent file remains uncovered
        $data->initializeUnseenData(
            RawCodeCoverageData::fromLineCoverage([
                $realFile => [1 => -1],
            ]),
        );

        $result = $this->processor->uncoveredFilesFromFilter($filter, $data, $analyser);

        $this->assertSame([], $result);
    }

    public function testUncoveredFilesFromFilterReturnsMultipleFiles(): void
    {
        $file1 = self::realpath(__DIR__ . '/../_files/BankAccount.php');
        $file2 = self::realpath(__DIR__ . '/../_files/CoveredClass.php');

        $filter = new Filter;
        $filter->includeFile($file1);
        $filter->includeFile($file2);

        $data     = new ProcessedCodeCoverageData;
        $analyser = new FileAnalyser(new ParsingSourceAnalyser, false, false);

        $result = $this->processor->uncoveredFilesFromFilter($filter, $data, $analyser);

        $this->assertCount(2, $result);
    }

    #[Ticket('https://bugs.xdebug.org/view.php?id=2438')]
    public function testUnreportedCodeUnitsReturnsDataSeededFromStaticAnalysisForFunctionsAndMethodsForWhichNoLineIsKnown(): void
    {
        $file = self::realpath(__DIR__ . '/../_files/source_with_two_methods.php');

        $this->assertSame(
            [$file => [[13 => -1]]],
            $this->unreportedCodeUnits($file, [8 => -1]),
        );
    }

    #[Ticket('https://bugs.xdebug.org/view.php?id=2438')]
    public function testUnreportedCodeUnitsReturnsAllLinesOfMethodForWhichNoLineIsKnown(): void
    {
        $file = self::realpath(__DIR__ . '/../_files/source_with_method_with_function_call_that_is_not_reported.php');

        $this->assertSame(
            [$file => [[13 => -1, 15 => -1]]],
            $this->unreportedCodeUnits($file, [8 => 1]),
        );
    }

    public function testUnreportedCodeUnitsReturnsNothingWhenLinesOfAllFunctionsAndMethodsAreKnown(): void
    {
        $file = self::realpath(__DIR__ . '/../_files/source_with_two_methods.php');

        $this->assertSame([], $this->unreportedCodeUnits($file, [8 => -1, 13 => -1]));
    }

    public function testUnreportedCodeUnitsMarksDeadCodeAsNotExecutable(): void
    {
        $file = self::realpath(__DIR__ . '/../_files/BankAccount.php');

        $this->assertSame(
            [$file => [[29 => -1, 31 => -1, 32 => -2]]],
            $this->unreportedCodeUnits($file, [8 => -1, 13 => -1, 14 => -1, 16 => -1, 22 => -1, 24 => -1]),
        );
    }

    public function testUnreportedCodeUnitsDoesNotReturnIgnoredLines(): void
    {
        $file = self::realpath(__DIR__ . '/../_files/source_with_ignore.php');

        $this->assertSame([], $this->unreportedCodeUnits($file, [2 => -1], true));
    }

    public function testUnreportedCodeUnitsReturnsIgnoredLinesWhenAnnotationsForIgnoringCodeAreNotUsed(): void
    {
        $file = self::realpath(__DIR__ . '/../_files/source_with_ignore.php');

        $this->assertSame(
            [$file => [[15 => -1], [25 => -1], [30 => -1]]],
            $this->unreportedCodeUnits($file, [2 => -1]),
        );
    }

    public function testUnreportedCodeUnitsReturnsDataSeededFromStaticAnalysisForMethodsOfTraits(): void
    {
        $file = self::realpath(__DIR__ . '/../_files/Target/two_traits.php');

        $this->assertSame(
            [$file => [[17 => -1]]],
            $this->unreportedCodeUnits($file, [8 => -1]),
        );
    }

    public function testUnreportedCodeUnitsSkipsFileThatCannotBeParsed(): void
    {
        $file = self::realpath(__DIR__ . '/../_files/source_that_cannot_be_parsed.php');

        $this->assertSame([], $this->unreportedCodeUnits($file, [5 => -1]));
    }

    public function testUnreportedCodeUnitsSkipsFileThatDoesNotExist(): void
    {
        $this->assertSame([], $this->unreportedCodeUnits('/does/not/exist.php', [8 => -1]));
    }

    /**
     * @param non-empty-string         $file
     * @param array<positive-int, int> $lines the lines as reported by a driver
     *
     * @return array<int, int>
     */
    private function lineCoverageAfterExecutableLinesFilter(string $file, array $lines): array
    {
        $data = RawCodeCoverageData::fromLineCoverage([$file => $lines]);

        $filter = new Filter;
        $filter->includeFile($file);

        $this->processor->applyExecutableLinesFilter(
            $data,
            $filter,
            new FileAnalyser(new ParsingSourceAnalyser, true, true),
        );

        $lineCoverage = $data->lineCoverage()[$file] ?? [];

        ksort($lineCoverage);

        return $lineCoverage;
    }

    /**
     * @param non-empty-string $file
     */
    private function dataFor(string $file): ProcessedCodeCoverageData
    {
        $data = new ProcessedCodeCoverageData;

        $data->initializeUnseenData(
            RawCodeCoverageData::fromLineCoverage([$file => [8 => -1]]),
        );

        return $data;
    }

    /**
     * @param non-empty-string         $file
     * @param array<positive-int, int> $knownLines
     *
     * @return array<non-empty-string, list<array<positive-int, int>>>
     */
    private function unreportedCodeUnits(string $file, array $knownLines, bool $useAnnotationsForIgnoringCode = false): array
    {
        $filter = new Filter;
        $filter->includeFile($file);

        $data = new ProcessedCodeCoverageData;

        $data->initializeUnseenData(
            RawCodeCoverageData::fromLineCoverage([$file => $knownLines]),
        );

        return $this->processor->unreportedCodeUnits(
            $filter,
            $data,
            new FileAnalyser(new ParsingSourceAnalyser, $useAnnotationsForIgnoringCode, false),
            $useAnnotationsForIgnoringCode,
        );
    }

    /**
     * @return TargetMap
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
            'files'                         => [],
            'directories'                   => [],
            'directoriesRecursively'        => [],
            'reverseLookup'                 => [],
        ];
    }

    private function emptyTargetCollection(): TargetCollection
    {
        return TargetCollection::fromArray([]);
    }

    private function createFileAnalyser(AnalysisResult $result): FileAnalyser
    {
        $sourceAnalyser = $this->createStub(SourceAnalyser::class);
        $sourceAnalyser->method('analyse')->willReturn($result);

        return new FileAnalyser($sourceAnalyser, false, false);
    }

    /**
     * @return non-empty-string
     */
    private static function realpath(string $path): string
    {
        $realpath = realpath($path);

        if ($realpath === false) {
            throw new RuntimeException('Could not resolve real path of ' . $path);
        }

        return $realpath;
    }
}
