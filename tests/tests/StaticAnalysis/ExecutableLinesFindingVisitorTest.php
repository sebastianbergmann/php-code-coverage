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
use function strpos;
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
        $this->doTestSelfDescribingAsset(TEST_FILES_PATH . 'source_for_branched_exec_lines.php');
    }

    /**
     * @requires PHP 7.4
     */
    public function testExecutableLinesAreGroupedByBranchPhp74(): void
    {
        $this->doTestSelfDescribingAsset(TEST_FILES_PATH . 'source_for_branched_exec_lines_php74.php');
    }

    /**
     * @requires PHP 8
     */
    public function testExecutableLinesAreGroupedByBranchPhp80(): void
    {
        $this->doTestSelfDescribingAsset(TEST_FILES_PATH . 'source_for_branched_exec_lines_php80.php');
    }

    /**
     * @requires PHP 8.1
     */
    public function testExecutableLinesAreGroupedByBranchPhp81(): void
    {
        $this->doTestSelfDescribingAsset(TEST_FILES_PATH . 'source_for_branched_exec_lines_php81.php');
    }

    /**
     * @requires PHP 8.2
     */
    public function testExecutableLinesAreGroupedByBranchPhp82(): void
    {
        $this->doTestSelfDescribingAsset(TEST_FILES_PATH . 'source_for_branched_exec_lines_php82.php');
    }

    private function doTestSelfDescribingAsset(string $filename): void
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
            if (false !== strpos($line, 'LINE_ADDED_IN_TEST')) {
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
