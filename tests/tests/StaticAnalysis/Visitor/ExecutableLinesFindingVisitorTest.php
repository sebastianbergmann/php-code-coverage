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

use function explode;
use function file_get_contents;
use function preg_match;
use function str_contains;
use PhpParser\NodeTraverser;
use PhpParser\ParserFactory;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RequiresPhp;
use PHPUnit\Framework\Attributes\Small;
use PHPUnit\Framework\Attributes\Ticket;
use PHPUnit\Framework\TestCase;

#[CoversClass(ExecutableLinesFindingVisitor::class)]
#[Small]
#[Group('static-analysis')]
final class ExecutableLinesFindingVisitorTest extends TestCase
{
    public function testExecutableLinesAreGroupedByBranch(): void
    {
        $this->doTestSelfDescribingAssert(TEST_FILES_PATH . 'source_for_branched_exec_lines.php');
    }

    #[RequiresPhp('>=8.1.0')]
    public function testExecutableLinesAreGroupedByBranchPhp81(): void
    {
        $this->doTestSelfDescribingAssert(TEST_FILES_PATH . 'source_for_branched_exec_lines_php81.php');
    }

    #[RequiresPhp('>=8.2.0')]
    public function testExecutableLinesAreGroupedByBranchPhp82(): void
    {
        $this->doTestSelfDescribingAssert(TEST_FILES_PATH . 'source_for_branched_exec_lines_php82.php');
    }

    public function testArrowFunctionIsProcessedCorrectly(): void
    {
        $source = file_get_contents(TEST_FILES_PATH . 'source_with_arrow_function.php');
        $parser = (new ParserFactory)->createForHostVersion();
        $nodes  = $parser->parse($source);

        $executableLinesFindingVisitor = new ExecutableLinesFindingVisitor($source);

        $traverser = new NodeTraverser;
        $traverser->addVisitor($executableLinesFindingVisitor);
        $traverser->traverse($nodes);

        $result = $executableLinesFindingVisitor->executableLinesGroupedByBranch();

        // The multi-line arrow function body should be an executable line
        $this->assertArrayHasKey(5, $result);
    }

    public function testEmptyForLoopIsProcessedCorrectly(): void
    {
        $source = file_get_contents(TEST_FILES_PATH . 'source_with_empty_for_loops.php');
        $parser = (new ParserFactory)->createForHostVersion();
        $nodes  = $parser->parse($source);

        $executableLinesFindingVisitor = new ExecutableLinesFindingVisitor($source);

        $traverser = new NodeTraverser;
        $traverser->addVisitor($executableLinesFindingVisitor);
        $traverser->traverse($nodes);

        $result = $executableLinesFindingVisitor->executableLinesGroupedByBranch();

        // for(;;) has no init/cond/loop, so no branch is set for the for statement
        // for(;; ++$i) has only loop part
        $this->assertNotEmpty($result);
    }

    #[Ticket('https://github.com/sebastianbergmann/phpunit/issues/6442')]
    public function testAbstractMethodDeclarationsAreNotExecutable(): void
    {
        $source                        = file_get_contents(__DIR__ . '/../../../_files/source_with_abstract_method.php');
        $parser                        = (new ParserFactory)->createForHostVersion();
        $nodes                         = $parser->parse($source);
        $executableLinesFindingVisitor = new ExecutableLinesFindingVisitor($source);

        $traverser = new NodeTraverser;
        $traverser->addVisitor($executableLinesFindingVisitor);
        $traverser->traverse($nodes);

        $executableLines = $executableLinesFindingVisitor->executableLinesGroupedByBranch();

        $this->assertArrayNotHasKey(6, $executableLines);
        $this->assertArrayNotHasKey(8, $executableLines);

        $this->assertArrayHasKey(12, $executableLines);
    }

    #[Ticket('https://github.com/sebastianbergmann/php-code-coverage/issues/967')]
    public function testMatchArmsAreProcessedCorrectly(): void
    {
        $source                        = file_get_contents(__DIR__ . '/../../../_files/source_match_expression.php');
        $parser                        = (new ParserFactory)->createForHostVersion();
        $nodes                         = $parser->parse($source);
        $executableLinesFindingVisitor = new ExecutableLinesFindingVisitor($source);

        $traverser = new NodeTraverser;
        $traverser->addVisitor($executableLinesFindingVisitor);
        $traverser->traverse($nodes);

        $this->assertSame(
            [
                8  => 2,
                9  => 3,
                10 => 4,
                11 => 5,
                12 => 6,
                13 => 7,
            ],
            $executableLinesFindingVisitor->executableLinesGroupedByBranch(),
        );
    }

    #[RequiresPhp('>=8.4.0')]
    public function testPropertyHooksAreProcessedCorrectly(): void
    {
        $source                        = file_get_contents(__DIR__ . '/../../../_files/source_with_property_hooks.php');
        $parser                        = (new ParserFactory)->createForHostVersion();
        $nodes                         = $parser->parse($source);
        $executableLinesFindingVisitor = new ExecutableLinesFindingVisitor($source);

        $traverser = new NodeTraverser;
        $traverser->addVisitor($executableLinesFindingVisitor);
        $traverser->traverse($nodes);

        $executableLines     = $executableLinesFindingVisitor->executableLinesGroupedByBranch();
        $branchOperatorLines = $executableLinesFindingVisitor->branchOperatorLines();

        $this->assertArrayNotHasKey(4, $executableLines);

        $this->assertArrayHasKey(9, $executableLines);
        $this->assertArrayHasKey(9, $branchOperatorLines);

        $this->assertArrayNotHasKey(10, $executableLines);
        $this->assertArrayHasKey(11, $executableLines);
        $this->assertArrayNotHasKey(12, $executableLines);

        $this->assertArrayHasKey(17, $executableLines);
    }

    public function testEmptyMatchExpressionIsProcessedCorrectly(): void
    {
        $source                        = file_get_contents(__DIR__ . '/../../../_files/source_with_empty_match.php');
        $parser                        = (new ParserFactory)->createForHostVersion();
        $nodes                         = $parser->parse($source);
        $executableLinesFindingVisitor = new ExecutableLinesFindingVisitor($source);

        $traverser = new NodeTraverser;
        $traverser->addVisitor($executableLinesFindingVisitor);
        $traverser->traverse($nodes);

        $executableLines = $executableLinesFindingVisitor->executableLinesGroupedByBranch();

        $this->assertArrayHasKey(4, $executableLines);
    }

    public function testBranchOperatorLinesAccessorReturnsLinesAddedByBranchHandlers(): void
    {
        $source                        = file_get_contents(__DIR__ . '/../../../_files/source_match_true_expression.php');
        $parser                        = (new ParserFactory)->createForHostVersion();
        $nodes                         = $parser->parse($source);
        $executableLinesFindingVisitor = new ExecutableLinesFindingVisitor($source);

        $traverser = new NodeTraverser;
        $traverser->addVisitor($executableLinesFindingVisitor);
        $traverser->traverse($nodes);

        $this->assertSame(
            [
                9  => true,
                10 => true,
                11 => true,
                12 => true,
            ],
            $executableLinesFindingVisitor->branchOperatorLines(),
        );
    }

    public function testCaseStatementsAreExecutableButNotBranchOperators(): void
    {
        $source                        = file_get_contents(__DIR__ . '/../../../_files/source_with_switch_case.php');
        $parser                        = (new ParserFactory)->createForHostVersion();
        $nodes                         = $parser->parse($source);
        $executableLinesFindingVisitor = new ExecutableLinesFindingVisitor($source);

        $traverser = new NodeTraverser;
        $traverser->addVisitor($executableLinesFindingVisitor);
        $traverser->traverse($nodes);

        $executableLines     = $executableLinesFindingVisitor->executableLinesGroupedByBranch();
        $branchOperatorLines = $executableLinesFindingVisitor->branchOperatorLines();

        $this->assertArrayHasKey(9, $executableLines);
        $this->assertArrayHasKey(11, $executableLines);

        $this->assertArrayNotHasKey(9, $branchOperatorLines);
        $this->assertArrayNotHasKey(11, $branchOperatorLines);
    }

    #[Ticket('https://github.com/sebastianbergmann/php-code-coverage/issues/1156')]
    public function testStatementsConsistingOfASideEffectFreeExpressionAreNotExecutable(): void
    {
        $source                        = file_get_contents(__DIR__ . '/../../../_files/source_dead_scalar_literal_statement.php');
        $parser                        = (new ParserFactory)->createForHostVersion();
        $nodes                         = $parser->parse($source);
        $executableLinesFindingVisitor = new ExecutableLinesFindingVisitor($source);

        $traverser = new NodeTraverser;
        $traverser->addVisitor($executableLinesFindingVisitor);
        $traverser->traverse($nodes);

        $executableLines     = $executableLinesFindingVisitor->executableLinesGroupedByBranch();
        $branchOperatorLines = $executableLinesFindingVisitor->branchOperatorLines();

        foreach ([8, 9, 10, 11, 12, 13] as $deadLine) {
            $this->assertArrayNotHasKey($deadLine, $executableLines);
            $this->assertArrayNotHasKey($deadLine, $branchOperatorLines);
        }

        $this->assertArrayHasKey(15, $executableLines);
    }

    #[Ticket('https://github.com/sebastianbergmann/php-code-coverage/issues/1154')]
    public function testMatchTrueDoesNotMarkOpenerAndCloserAsExecutable(): void
    {
        $source                        = file_get_contents(__DIR__ . '/../../../_files/source_match_true_expression.php');
        $parser                        = (new ParserFactory)->createForHostVersion();
        $nodes                         = $parser->parse($source);
        $executableLinesFindingVisitor = new ExecutableLinesFindingVisitor($source);

        $traverser = new NodeTraverser;
        $traverser->addVisitor($executableLinesFindingVisitor);
        $traverser->traverse($nodes);

        $executableLines = $executableLinesFindingVisitor->executableLinesGroupedByBranch();

        $this->assertArrayNotHasKey(8, $executableLines);
        $this->assertArrayNotHasKey(13, $executableLines);
        $this->assertArrayHasKey(9, $executableLines);
        $this->assertArrayHasKey(10, $executableLines);
        $this->assertArrayHasKey(11, $executableLines);
        $this->assertArrayHasKey(12, $executableLines);
    }

    #[Ticket('https://github.com/sebastianbergmann/php-code-coverage/issues/1159')]
    public function testStatementsInClosureNestedInCallArgumentAreNotMarkedAsBranchOperators(): void
    {
        $source                        = file_get_contents(__DIR__ . '/../../../_files/source_callable_inside_call_argument.php');
        $parser                        = (new ParserFactory)->createForHostVersion();
        $nodes                         = $parser->parse($source);
        $executableLinesFindingVisitor = new ExecutableLinesFindingVisitor($source);

        $traverser = new NodeTraverser;
        $traverser->addVisitor($executableLinesFindingVisitor);
        $traverser->traverse($nodes);

        $executableLines     = $executableLinesFindingVisitor->executableLinesGroupedByBranch();
        $branchOperatorLines = $executableLinesFindingVisitor->branchOperatorLines();

        $this->assertArrayHasKey(8, $executableLines);
        $this->assertArrayHasKey(10, $executableLines);

        $this->assertArrayNotHasKey(8, $branchOperatorLines);
        $this->assertArrayNotHasKey(10, $branchOperatorLines);

        $this->assertArrayHasKey(11, $branchOperatorLines);
    }

    private function doTestSelfDescribingAssert(string $filename): void
    {
        $source                        = file_get_contents($filename);
        $parser                        = (new ParserFactory)->createForHostVersion();
        $nodes                         = $parser->parse($source);
        $executableLinesFindingVisitor = new ExecutableLinesFindingVisitor($source);

        $traverser = new NodeTraverser;
        $traverser->addVisitor($executableLinesFindingVisitor);
        $traverser->traverse($nodes);

        $executableLinesGroupedByBranch = $executableLinesFindingVisitor->executableLinesGroupedByBranch();

        $linesFromSource = explode("\n", $source);
        $expected        = [];

        $branch = 0;

        foreach ($linesFromSource as $lineNumber => $line) {
            if (str_contains($line, 'LINE_ADDED_IN_TEST')) {
                $expected[1 + $lineNumber] = $branch;

                continue;
            }

            if (1 !== preg_match('#^\s*[^/].+// (?<branchIncrement>[+-]?\d+)$#', $line, $matches)) {
                continue;
            }

            $branch += (int) ($matches['branchIncrement']);
            $expected[1 + $lineNumber] = $branch;
        }

        $this->assertEquals(
            $expected,
            $executableLinesGroupedByBranch,
        );
    }
}
