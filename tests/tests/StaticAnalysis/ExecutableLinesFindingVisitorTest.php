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
use PhpParser\Lexer;
use PhpParser\NodeTraverser;
use PhpParser\ParserFactory;
use PHPUnit\Framework\TestCase;

/**
 * @covers \SebastianBergmann\CodeCoverage\StaticAnalysis\ExecutableLinesFindingVisitor
 */
final class ExecutableLinesFindingVisitorTest extends TestCase
{
    public function testExecutableLinesAreGroupedByBranch(): void
    {
        $source = file_get_contents(TEST_FILES_PATH . 'source_for_branched_exec_lines.php');
        $parser = (new ParserFactory)->create(
            ParserFactory::PREFER_PHP7,
            new Lexer
        );
        $nodes                         = $parser->parse($source);
        $executableLinesFindingVisitor = new ExecutableLinesFindingVisitor;

        $traverser = new NodeTraverser;
        $traverser->addVisitor($executableLinesFindingVisitor);
        $traverser->traverse($nodes);

        $executableLinesGroupedByBranch = $executableLinesFindingVisitor->executableLinesGroupedByBranch();

        $linesFromSource = explode("\n", $source);
        $expected        = [];

        $branch = 0;

        foreach ($linesFromSource as $lineNumber => $line) {
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
