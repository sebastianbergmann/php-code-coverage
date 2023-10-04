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
use PhpParser\Lexer;
use PhpParser\NodeTraverser;
use PhpParser\ParserFactory;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RequiresPhp;
use PHPUnit\Framework\TestCase;

#[CoversClass(ExecutableLinesFindingVisitor::class)]
final class ExecutableLinesFindingVisitorTest extends TestCase
{
    public function testExecutableLinesAreGroupedByBranch(): void
    {
        $this->doTestSelfDescribingAsset(TEST_FILES_PATH . 'source_for_branched_exec_lines.php');
    }

    #[RequiresPhp('>=8.1')]
    public function testExecutableLinesAreGroupedByBranchPhp81(): void
    {
        $this->doTestSelfDescribingAsset(TEST_FILES_PATH . 'source_for_branched_exec_lines_php81.php');
    }

    #[RequiresPhp('>=8.2')]
    public function testExecutableLinesAreGroupedByBranchPhp82(): void
    {
        $this->doTestSelfDescribingAsset(TEST_FILES_PATH . 'source_for_branched_exec_lines_php82.php');
    }

    private function doTestSelfDescribingAsset(string $filename): void
    {
        $source = file_get_contents($filename);
        $parser = (new ParserFactory)->create(
            ParserFactory::PREFER_PHP7,
            new Lexer
        );
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
            $executableLinesGroupedByBranch
        );
    }
}
