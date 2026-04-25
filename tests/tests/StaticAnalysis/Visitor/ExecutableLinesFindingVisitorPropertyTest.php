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
use function array_slice;
use function count;
use function explode;
use function implode;
use function preg_match;
use function preg_replace;
use function str_replace;
use function substr_count;
use function trim;
use PhpParser\Error;
use PhpParser\NodeTraverser;
use PhpParser\ParserFactory;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Small;
use PHPUnit\Framework\TestCase;

/**
 * Property-style tests for {@see ExecutableLinesFindingVisitor}.
 *
 * Each test method states an invariant that must hold for every source the
 * visitor sees, then iterates over a generated set of sources (snippet
 * templates * whitespace/comment mutators) and asserts the invariant on each.
 *
 * No external property-testing dependency is used; the generators are plain
 * iterables. When an invariant fails, up to five offending sources are shown
 * in the failure message so the regression can be reproduced quickly.
 */
#[CoversClass(ExecutableLinesFindingVisitor::class)]
#[Small]
#[Group('static-analysis')]
final class ExecutableLinesFindingVisitorPropertyTest extends TestCase
{
    public function testBranchOperatorLinesAreAlwaysSubsetOfExecutableMap(): void
    {
        $violations = [];

        foreach ($this->generatedSources() as [$label, $source]) {
            $result = $this->analyse($source);

            if ($result === null) {
                continue;
            }

            [$exec, $branchOp] = $result;

            foreach (array_keys($branchOp) as $line) {
                if (!isset($exec[$line])) {
                    $violations[] = [$label, "line {$line} in branchOperatorLines but not in executableLinesGroupedByBranch", $source];
                }
            }
        }

        $this->failOnViolations($violations);
    }

    public function testEveryMarkedLineLiesWithinSourceBounds(): void
    {
        $violations = [];

        foreach ($this->generatedSources() as [$label, $source]) {
            $result = $this->analyse($source);

            if ($result === null) {
                continue;
            }

            [$exec, $branchOp] = $result;
            $maxLine           = substr_count(str_replace("\r\n", "\n", $source), "\n") + 1;

            foreach (array_keys($exec + $branchOp) as $line) {
                if ($line < 1 || $line > $maxLine) {
                    $violations[] = [$label, "line {$line} out of [1, {$maxLine}]", $source];
                }
            }
        }

        $this->failOnViolations($violations);
    }

    public function testEveryBranchIdIsPositive(): void
    {
        $violations = [];

        foreach ($this->generatedSources() as [$label, $source]) {
            $result = $this->analyse($source);

            if ($result === null) {
                continue;
            }

            [$exec] = $result;

            foreach ($exec as $line => $branch) {
                if ($branch < 1) {
                    $violations[] = [$label, "line {$line} has branch id {$branch}", $source];
                }
            }
        }

        $this->failOnViolations($violations);
    }

    public function testNoBlankLineIsMarked(): void
    {
        $violations = [];

        foreach ($this->generatedSources() as [$label, $source]) {
            $result = $this->analyse($source);

            if ($result === null) {
                continue;
            }

            [$exec] = $result;
            $lines  = explode("\n", str_replace("\r\n", "\n", $source));

            foreach (array_keys($exec) as $line) {
                $content = $lines[$line - 1] ?? '';

                if (preg_match('/^\s*$/', $content) === 1) {
                    $violations[] = [$label, "blank line {$line} marked as executable", $source];
                }
            }
        }

        $this->failOnViolations($violations);
    }

    public function testNoSingleLineCommentOnlyLineIsMarked(): void
    {
        $violations = [];

        foreach ($this->generatedSources() as [$label, $source]) {
            $result = $this->analyse($source);

            if ($result === null) {
                continue;
            }

            [$exec] = $result;
            $lines  = explode("\n", str_replace("\r\n", "\n", $source));

            foreach (array_keys($exec) as $line) {
                $content = $lines[$line - 1] ?? '';

                if (preg_match('#^\s*//#', $content) === 1) {
                    $violations[] = [$label, "comment-only line {$line} marked: " . trim($content), $source];
                }
            }
        }

        $this->failOnViolations($violations);
    }

    public function testTraversalIsIdempotent(): void
    {
        $violations = [];

        foreach ($this->generatedSources() as [$label, $source]) {
            $first  = $this->analyse($source);
            $second = $this->analyse($source);

            if ($first === null || $second === null) {
                continue;
            }

            if ($first !== $second) {
                $violations[] = [$label, 'two runs over the same source produced different output', $source];
            }
        }

        $this->failOnViolations($violations);
    }

    public function testLineEndingsDoNotAffectOutput(): void
    {
        $violations = [];

        foreach ($this->generatedSources() as [$label, $source]) {
            $lf   = str_replace("\r\n", "\n", $source);
            $crlf = str_replace("\n", "\r\n", $lf);

            $a = $this->analyse($lf);
            $b = $this->analyse($crlf);

            if ($a === null || $b === null) {
                continue;
            }

            if ($a !== $b) {
                $violations[] = [$label, 'LF and CRLF variants produced different output', $source];
            }
        }

        $this->failOnViolations($violations);
    }

    public function testNoStructuralOnlyLineAppearsInBranchOperatorLines(): void
    {
        $violations = [];

        foreach ($this->generatedSources() as [$label, $source]) {
            $result = $this->analyse($source);

            if ($result === null) {
                continue;
            }

            [, $branchOp] = $result;
            $lines        = explode("\n", str_replace("\r\n", "\n", $source));

            foreach (array_keys($branchOp) as $line) {
                $content = $lines[$line - 1] ?? '';

                if (preg_match('/^[\s})\];,]+$/', $content) === 1) {
                    $violations[] = [$label, "structural-only line {$line} in branchOperatorLines: " . trim($content), $source];
                }
            }
        }

        $this->failOnViolations($violations);
    }

    /**
     * @return iterable<array{string, string}>
     */
    private function generatedSources(): iterable
    {
        foreach ($this->snippets() as $snippetLabel => $body) {
            foreach ($this->mutators() as $mutatorLabel => $mutator) {
                yield [$snippetLabel . '/' . $mutatorLabel, $mutator($this->wrap($body))];
            }
        }
    }

    /**
     * Snippet templates exercising the visitor's specific node handlers.
     *
     * @return iterable<string, string>
     */
    private function snippets(): iterable
    {
        yield 'simple-return' => 'return 1;';

        yield 'if-else' => "if (\$x) {\n    return 1;\n} else {\n    return 2;\n}";

        yield 'if-elseif-else' => "if (\$x === 1) {\n    return 1;\n} elseif (\$x === 2) {\n    return 2;\n} else {\n    return 3;\n}";

        yield 'ternary-single-line' => 'return $x ? 1 : 2;';

        yield 'ternary-multi-line' => "return \$x\n    ? 1\n    : 2;";

        yield 'ternary-short' => 'return $x ?: 0;';

        yield 'coalesce-single-line' => 'return $x ?? 0;';

        yield 'coalesce-multi-line' => "return \$x\n    ?? 0;";

        yield 'foreach' => "foreach (\$x as \$v) {\n    echo \$v;\n}";

        yield 'for' => "for (\$i = 0; \$i < 10; \$i++) {\n    echo \$i;\n}";

        yield 'while' => "while (\$x) {\n    \$x--;\n}";

        yield 'do-while' => "do {\n    \$x--;\n} while (\$x > 0);";

        yield 'match-variable' => "return match (\$x) {\n    1 => 'a',\n    default => 'b',\n};";

        yield 'match-true' => "return match (true) {\n    \$x === 1 => 'a',\n    default  => 'b',\n};";

        yield 'match-single-line' => "return match (\$x) { 1 => 'a', default => 'b' };";

        yield 'try-catch' => "try {\n    foo();\n} catch (\\RuntimeException \$e) {\n    return 0;\n}";

        yield 'try-catch-finally' => "try {\n    foo();\n} catch (\\RuntimeException \$e) {\n    return 0;\n} finally {\n    cleanup();\n}";

        yield 'arrow-function' => "\$f = fn(\$y) => \$y + 1;\nreturn \$f(\$x);";

        yield 'arrow-function-multi' => "\$f = fn(\$y) =>\n    \$y + 1;\nreturn \$f(\$x);";

        yield 'closure' => "\$f = function (\$y) { return \$y + 1; };\nreturn \$f(\$x);";

        yield 'array-multiline' => "return [\n    'a' => 1,\n    'b' => 2,\n];";

        yield 'method-chain' => "return \$x\n    ->foo()\n    ->bar()\n    ->baz();";

        yield 'nested-ternary-coalesce' => "return \$x\n    ? (\$y ?? 'a')\n    : 'b';";

        yield 'ternary-inside-array' => "return [\n    'k' => \$x\n        ? 'a'\n        : 'b',\n];";

        yield 'switch' => "switch (\$x) {\n    case 0:\n        return 'a';\n    default:\n        return 'b';\n}";
    }

    /**
     * Source-level mutators that should not, for the asserted invariants,
     * change the visitor's behaviour in ways that violate the property.
     *
     * @return iterable<string, callable(string): string>
     */
    private function mutators(): iterable
    {
        yield 'identity' => static fn (string $s): string => $s;

        yield 'crlf' => static fn (string $s): string => str_replace("\n", "\r\n", $s);

        yield 'trailing-blank' => static fn (string $s): string => $s . "\n\n";

        yield 'tabbed-indent' => static fn (string $s): string => str_replace('    ', "\t", $s);

        yield 'extra-indent' => static fn (string $s): string => (string) preg_replace('/^(    )/m', '        ', $s);

        yield 'comment-prefixed' => static fn (string $s): string => (string) preg_replace('#^(    [^ /])#m', "    // c\n$1", $s, 1);
    }

    private function wrap(string $body): string
    {
        return "<?php declare(strict_types=1);\nfunction generated(\$x, \$y) {\n    " . str_replace("\n", "\n    ", $body) . "\n}\n";
    }

    /**
     * @return null|array{0: array<int, int>, 1: array<int, true>}
     */
    private function analyse(string $source): ?array
    {
        try {
            $nodes = (new ParserFactory)->createForHostVersion()->parse($source);
        } catch (Error) {
            return null;
        }

        if ($nodes === null) {
            return null;
        }

        $visitor   = new ExecutableLinesFindingVisitor($source);
        $traverser = new NodeTraverser;
        $traverser->addVisitor($visitor);
        $traverser->traverse($nodes);

        return [
            $visitor->executableLinesGroupedByBranch(),
            $visitor->branchOperatorLines(),
        ];
    }

    /**
     * @param list<array{string, string, string}> $violations
     */
    private function failOnViolations(array $violations): void
    {
        if ($violations === []) {
            $this->assertSame([], $violations);

            return;
        }

        $messages = [];

        foreach (array_slice($violations, 0, 5) as [$label, $message, $source]) {
            $messages[] = "[{$label}] {$message}\n--- source ---\n{$source}--------------";
        }

        $extra = count($violations) > 5 ? "\n(... and " . (count($violations) - 5) . ' more)' : '';

        $this->fail(implode("\n\n", $messages) . $extra);
    }
}
