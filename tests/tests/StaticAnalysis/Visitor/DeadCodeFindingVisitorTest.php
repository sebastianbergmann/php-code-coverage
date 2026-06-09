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

use function array_keys;
use function assert;
use function file_get_contents;
use PhpParser\NodeTraverser;
use PhpParser\ParserFactory;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Small;
use PHPUnit\Framework\TestCase;

#[CoversClass(DeadCodeFindingVisitor::class)]
#[Small]
#[Group('static-analysis')]
final class DeadCodeFindingVisitorTest extends TestCase
{
    public function testIdentifiesLinesUnreachableFromTheStaticControlFlow(): void
    {
        $deadLines = $this->deadLines(TEST_FILES_PATH . 'source_with_dead_code.php');

        // The raw set includes wrapper-boundary lines (e.g. `} else {` and the
        // closing `}` of the dead block) that are not executable on their own.
        // ParsingSourceAnalyser intersects this set with the executable-lines
        // map before publishing it, which drops those boundaries.
        $this->assertSame(
            [
                9,   // after return
                10,  // after return
                16,  // after throw statement
                22,  // after throw statement (second occurrence)
                28,  // after exit
                35,  // after break
                43,  // after continue
                50,  // body of if (false)
                51,  // body of if (false)
                59,  // `} else {` wrapper line, dropped by the intersection
                60,  // dead body of else after if (true)
                61,  // closing `}` wrapper line, dropped by the intersection
                68,  // elseif after if (true) — condition line itself
                69,  // dead body of elseif after if (true)
                70,  // closing `}` wrapper line, dropped by the intersection
                80,  // body of elseif (false)
                89,  // body of while (false)
                96,  // body of for (...; false; ...)
                103, // dead arm of ternary with literal-false condition
            ],
            array_keys($deadLines),
        );
    }

    public function testReportsLiveCodeAsNotDead(): void
    {
        $deadLines = $this->deadLines(TEST_FILES_PATH . 'source_with_dead_code.php');

        foreach ([8, 15, 21, 27, 34, 42, 49, 58, 67, 72, 78, 83, 88, 95, 104, 110, 113] as $liveLine) {
            $this->assertArrayNotHasKey($liveLine, $deadLines);
        }
    }

    public function testReportsNoDeadLinesForSourceWithoutAny(): void
    {
        $deadLines = $this->deadLines(TEST_FILES_PATH . 'source_without_dead_code.php');

        $this->assertSame([], $deadLines);
    }

    /**
     * @return array<positive-int, true>
     */
    private function deadLines(string $file): array
    {
        $source = file_get_contents($file);
        assert($source !== false);

        $parser = (new ParserFactory)->createForHostVersion();
        $nodes  = $parser->parse($source);
        assert($nodes !== null);

        $visitor = new DeadCodeFindingVisitor;

        $traverser = new NodeTraverser;
        $traverser->addVisitor($visitor);
        $traverser->traverse($nodes);

        return $visitor->deadLines();
    }
}
