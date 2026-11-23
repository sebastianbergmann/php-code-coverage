<?php declare(strict_types=1);
/*
 * This file is part of phpunit/php-code-coverage.
 *
 * (c) Sebastian Bergmann <sebastian@phpunit.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace SebastianBergmann\CodeCoverage\StaticAnalysis;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Small;
use PHPUnit\Framework\Attributes\TestDox;
use PHPUnit\Framework\TestCase;
use SebastianBergmann\CodeCoverage\Serialization\FileCouldNotBeReadException;

#[CoversClass(SourceLines::class)]
#[Group('static-analysis')]
#[Small]
final class SourceLinesTest extends TestCase
{
    /**
     * @return non-empty-array<non-empty-string, array{list<positive-int>, positive-int}>
     */
    public static function statementProvider(): array
    {
        return [
            'first line of statement that spans multiple lines'           => [[7, 8, 9, 10, 11], 7],
            'line of argument of function call that spans multiple lines' => [[7, 8, 9, 10, 11], 10],
            'last line of statement that spans multiple lines'            => [[7, 8, 9, 10, 11], 11],
            'line of left operand of && operator'                         => [[13], 13],
            'line of right operand of && operator'                        => [[14], 14],
            'statement on single line'                                    => [[18], 18],
            'first line of statement that declares anonymous class'       => [[27, 32], 27],
            'statement in method of anonymous class'                      => [[30], 30],
            'line of declaration of function'                             => [[], 4],
            'line of comment'                                             => [[], 6],
            'empty line'                                                  => [[], 12],
            'line of closing brace of control structure'                  => [[], 16],
            'line of declaration of property'                             => [[], 23],
            'line of declaration of method of anonymous class'            => [[], 28],
            'line that does not exist'                                    => [[], 1000],
        ];
    }

    /**
     * @return non-empty-array<non-empty-string, array{?non-empty-string, positive-int}>
     */
    public static function codeUnitProvider(): array
    {
        return [
            'first line of function'                  => ['SebastianBergmann\CodeCoverage\TestFixture\SourceLines\greet', 4],
            'line in function'                        => ['SebastianBergmann\CodeCoverage\TestFixture\SourceLines\greet', 10],
            'last line of function'                   => ['SebastianBergmann\CodeCoverage\TestFixture\SourceLines\greet', 19],
            'line in method of class'                 => ['SebastianBergmann\CodeCoverage\TestFixture\SourceLines\Greeter::greet', 34],
            'line in method of anonymous class'       => ['SebastianBergmann\CodeCoverage\TestFixture\SourceLines\Greeter::greet', 30],
            'line in method of trait'                 => ['SebastianBergmann\CodeCoverage\TestFixture\SourceLines\Greeting::greeting', 42],
            'line in method of enumeration'           => ['SebastianBergmann\CodeCoverage\TestFixture\SourceLines\Salutation::text', 53],
            'line between functions and classes'      => [null, 20],
            'line of declaration of class'            => [null, 21],
            'line of declaration of property'         => [null, 23],
            'line of declaration of enumeration case' => [null, 48],
            'line that does not exist'                => [null, 1000],
        ];
    }

    /**
     * @param list<positive-int> $expected
     * @param positive-int       $line
     */
    #[DataProvider('statementProvider')]
    #[TestDox('Knows the lines of the statement that a line belongs to: $_dataName')]
    public function testKnowsTheLinesOfTheStatementThatLineBelongsTo(array $expected, int $line): void
    {
        $this->assertSame(
            $expected,
            SourceLines::of(__DIR__ . '/../../_files/source_for_source_lines.php')->linesOfStatementAt($line),
        );
    }

    /**
     * @param ?non-empty-string $expected
     * @param positive-int      $line
     */
    #[DataProvider('codeUnitProvider')]
    #[TestDox('Knows the method or function that a line belongs to: $_dataName')]
    public function testKnowsTheMethodOrFunctionThatLineBelongsTo(?string $expected, int $line): void
    {
        $this->assertSame(
            $expected,
            SourceLines::of(__DIR__ . '/../../_files/source_for_source_lines.php')->codeUnitAt($line),
        );
    }

    public function testCanBeCreatedFromResultOfAnalysisThatWasAlreadyPerformed(): void
    {
        $sourceLines = SourceLines::fromAnalysisResult(
            new AnalysisResult(
                [],
                [],
                [],
                ['example\\f' => new Function_('f', 'example\\f', 'example', 1, 6, 'function f(): void', 1)],
                new LinesOfCode(6, 0, 6),
                [2 => 2, 5 => 5],
                [],
                [],
                [],
                null,
                [2 => 1, 3 => 1, 4 => 1, 5 => 2],
            ),
        );

        $this->assertSame([2, 3, 4], $sourceLines->linesOfStatementAt(2));
        $this->assertSame([2, 3, 4], $sourceLines->linesOfStatementAt(3));
        $this->assertSame([5], $sourceLines->linesOfStatementAt(5));
        $this->assertSame('example\\f', $sourceLines->codeUnitAt(3));
    }

    public function testKnowsNothingAboutTheLinesOfFileThatCannotBeParsed(): void
    {
        $sourceLines = SourceLines::of(__DIR__ . '/../../_files/source_that_cannot_be_parsed.php');

        $this->assertSame([], $sourceLines->linesOfStatementAt(1));
        $this->assertSame([], $sourceLines->linesOfStatementAt(2));
        $this->assertSame([], $sourceLines->linesOfStatementAt(3));
        $this->assertNull($sourceLines->codeUnitAt(1));
        $this->assertNull($sourceLines->codeUnitAt(2));
        $this->assertNull($sourceLines->codeUnitAt(3));
    }

    public function testCannotBeCreatedForFileThatDoesNotExist(): void
    {
        $this->expectException(FileCouldNotBeReadException::class);
        $this->expectExceptionMessageIs('File "' . __DIR__ . '/does-not-exist.php" could not be read');

        SourceLines::of(__DIR__ . '/does-not-exist.php');
    }
}
