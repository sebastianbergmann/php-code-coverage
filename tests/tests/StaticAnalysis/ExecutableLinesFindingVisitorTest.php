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
use PHPUnit\Framework\Attributes\RequiresPhp;
use PHPUnit\Framework\Attributes\Ticket;
use PHPUnit\Framework\TestCase;

#[CoversClass(ExecutableLinesFindingVisitor::class)]
final class ExecutableLinesFindingVisitorTest extends TestCase
{
    public function testExecutableLinesAreGroupedByBranch(): void
    {
        $this->doTestSelfDescribingAssert(TEST_FILES_PATH . 'source_for_branched_exec_lines.php');
    }

    #[RequiresPhp('>=8.1')]
    public function testExecutableLinesAreGroupedByBranchPhp81(): void
    {
        $this->doTestSelfDescribingAssert(TEST_FILES_PATH . 'source_for_branched_exec_lines_php81.php');
    }

    #[RequiresPhp('>=8.2')]
    public function testExecutableLinesAreGroupedByBranchPhp82(): void
    {
        $this->doTestSelfDescribingAssert(TEST_FILES_PATH . 'source_for_branched_exec_lines_php82.php');
    }

    #[Ticket('https://github.com/sebastianbergmann/php-code-coverage/issues/967')]
    public function testMatchArmsAreProcessedCorrectly(): void
    {
        $source                        = file_get_contents(__DIR__ . '/../../_files/source_match_expression.php');
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
                14 => 2,
            ],
            $executableLinesFindingVisitor->executableLinesGroupedByBranch(),
        );
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
