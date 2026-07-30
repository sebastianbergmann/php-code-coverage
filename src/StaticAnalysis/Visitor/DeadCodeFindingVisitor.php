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

use function array_key_last;
use function ksort;
use function max;
use function min;
use function strtolower;
use PhpParser\Node;
use PhpParser\NodeFinder;
use PhpParser\NodeVisitorAbstract;

/**
 * Identifies lines that the PHP-Parser AST shows to be statically unreachable.
 *
 * The visitor recognizes three structural patterns:
 *
 *  - Statements that follow an unconditional control-flow transfer within the
 *    same `stmts` array (`return`, `throw`, exit/die, `break`, `continue`, `goto`).
 *    An `if` statement whose every reachable branch ends in such a transfer
 *    (an exhaustive `if`/`elseif`/`else` chain, or an `if (true)`) counts as an
 *    unconditional transfer itself, as does an infinite loop (`while (true)`,
 *    `do {} while (true)`, `for (;;)`) whose body contains neither `break`
 *    nor `goto`, a `match` statement whose every arm throws or exits, and a
 *    `switch` statement with a `default` case whose control flow cannot leave
 *    the switch normally (no `break` or `continue`, terminating final case).
 *  - Bodies of branches with literal-constant conditions: `if (false) { ... }`,
 *    `elseif (false) { ... }`, `while (false) { ... }`, `for (...; false; ...) { ... }`,
 *    the `elseif`/`else` tail after an `if (true)`, and the unreachable arm of
 *    a ternary with a literal-constant condition.
 *
 * Dead-line reporting is line-based while reachability is statement-based: a
 * line that also carries reachable code (dead code following a terminator on
 * the terminator's own line, a dead `else` sharing a line with the live `if`
 * body) must never be reported. Dead ranges are therefore clipped to the lines
 * past the last line that contains reachable code.
 *
 * A label makes the code that follows it reachable again via `goto`. Since
 * `goto` may also jump into a conditional block (only loops and switches are
 * forbidden jump targets), a statement or block that contains a label anywhere
 * in its subtree is never reported as dead.
 *
 * Whole-program reasoning (never-called functions, opcode-level optimization)
 * is out of scope; the visitor reports only what is locally derivable from the
 * AST.
 *
 * @internal This class is not covered by the backward compatibility promise for phpunit/php-code-coverage
 *
 * @no-named-arguments Parameter names are not covered by the backward compatibility promise for phpunit/php-code-coverage
 */
final class DeadCodeFindingVisitor extends NodeVisitorAbstract
{
    /**
     * @var array<positive-int, true>
     */
    private array $deadLines = [];

    public function enterNode(Node $node): null
    {
        $stmts = $this->statementsOf($node);

        if ($stmts !== []) {
            $this->markStatementsAfterTerminator($stmts);
        }

        if ($node instanceof Node\Stmt\If_) {
            $this->handleIf($node);

            return null;
        }

        if ($node instanceof Node\Stmt\ElseIf_ && $this->isLiteralFalse($node->cond)) {
            $this->markBlock($node->stmts, $node);

            return null;
        }

        if ($node instanceof Node\Stmt\While_ && $this->isLiteralFalse($node->cond)) {
            $this->markBlock($node->stmts, $node);

            return null;
        }

        if ($node instanceof Node\Stmt\For_ && $this->forBodyIsUnreachable($node)) {
            $this->markBlock($node->stmts, $node);

            return null;
        }

        if ($node instanceof Node\Expr\Ternary) {
            $this->handleTernary($node);

            return null;
        }

        return null;
    }

    /**
     * @return array<positive-int, true>
     */
    public function deadLines(): array
    {
        ksort($this->deadLines);

        return $this->deadLines;
    }

    private function handleIf(Node\Stmt\If_ $node): void
    {
        if ($this->isLiteralFalse($node->cond)) {
            $this->markBlock($node->stmts, $node);

            return;
        }

        if (!$this->isLiteralTrue($node->cond)) {
            return;
        }

        $lastLiveLine = $node->cond->getEndLine();
        $liveStmts    = $node->stmts;

        if ($liveStmts !== []) {
            $lastLiveLine = $liveStmts[array_key_last($liveStmts)]->getEndLine();
        }

        foreach ($node->elseifs as $elseif) {
            if ($this->containsLabel($elseif->stmts)) {
                $lastLiveLine = max($lastLiveLine, $elseif->getEndLine());

                continue;
            }

            $this->markRange(max($elseif->getStartLine(), $lastLiveLine + 1), $elseif->getEndLine());
        }

        if ($node->else !== null && !$this->containsLabel($node->else->stmts)) {
            $this->markRange(max($node->else->getStartLine(), $lastLiveLine + 1), $node->else->getEndLine());
        }
    }

    private function handleTernary(Node\Expr\Ternary $node): void
    {
        if ($node->getStartLine() === $node->getEndLine()) {
            return;
        }

        if ($this->isLiteralTrue($node->cond)) {
            if ($node->if !== null) {
                $lastLiveLine = $node->if->getEndLine();
            } else {
                $lastLiveLine = $node->cond->getEndLine();
            }

            $this->markRange(max($node->else->getStartLine(), $lastLiveLine + 1), $node->else->getEndLine());

            return;
        }

        if ($this->isLiteralFalse($node->cond) && $node->if !== null) {
            $this->markRange(
                max($node->if->getStartLine(), $node->cond->getEndLine() + 1),
                min($node->if->getEndLine(), $node->else->getStartLine() - 1),
            );
        }
    }

    /**
     * @param array<Node\Stmt> $stmts
     */
    private function markStatementsAfterTerminator(array $stmts): void
    {
        $terminated   = false;
        $lastLiveLine = 0;

        foreach ($stmts as $stmt) {
            if (!$terminated) {
                if ($this->isTerminator($stmt)) {
                    $terminated   = true;
                    $lastLiveLine = $stmt->getEndLine();
                }

                continue;
            }

            if ($this->containsLabel([$stmt])) {
                $terminated = false;

                continue;
            }

            if (!$stmt instanceof Node\Stmt\Nop) {
                $this->markRange(max($stmt->getStartLine(), $lastLiveLine + 1), $stmt->getEndLine());
            }
        }
    }

    /**
     * @param array<Node\Stmt> $stmts
     */
    private function markBlock(array $stmts, Node $wrapper): void
    {
        if ($this->containsLabel($stmts)) {
            return;
        }

        $wrapperStart = $wrapper->getStartLine();
        $wrapperEnd   = $wrapper->getEndLine();

        foreach ($stmts as $stmt) {
            if ($stmt instanceof Node\Stmt\Nop) {
                continue;
            }

            for ($line = $stmt->getStartLine(); $line <= $stmt->getEndLine(); $line++) {
                if ($line === $wrapperStart || $line === $wrapperEnd) {
                    continue;
                }

                if ($line < 1) {
                    continue;
                }

                $this->deadLines[$line] = true;
            }
        }
    }

    private function markRange(int $start, int $end): void
    {
        for ($line = $start; $line <= $end; $line++) {
            if ($line < 1) {
                continue;
            }

            $this->deadLines[$line] = true;
        }
    }

    private function isTerminator(Node\Stmt $stmt): bool
    {
        if ($stmt instanceof Node\Stmt\Return_ ||
            $stmt instanceof Node\Stmt\Break_ ||
            $stmt instanceof Node\Stmt\Continue_ ||
            $stmt instanceof Node\Stmt\Goto_) {
            return true;
        }

        if ($stmt instanceof Node\Stmt\Expression) {
            return $this->expressionTerminates($stmt->expr);
        }

        if ($stmt instanceof Node\Stmt\If_) {
            return $this->ifAlwaysTerminates($stmt);
        }

        if ($stmt instanceof Node\Stmt\While_ && $this->isLiteralTrue($stmt->cond)) {
            return $this->infiniteLoopTerminates($stmt->stmts);
        }

        if ($stmt instanceof Node\Stmt\Do_ && $this->isLiteralTrue($stmt->cond)) {
            return $this->infiniteLoopTerminates($stmt->stmts);
        }

        if ($stmt instanceof Node\Stmt\For_ && $this->forLoopsForever($stmt)) {
            return $this->infiniteLoopTerminates($stmt->stmts);
        }

        if ($stmt instanceof Node\Stmt\Switch_) {
            return $this->switchAlwaysTerminates($stmt);
        }

        return false;
    }

    private function switchAlwaysTerminates(Node\Stmt\Switch_ $node): bool
    {
        $cases = $node->cases;

        if ($cases === []) {
            return false;
        }

        $hasDefault = false;

        foreach ($cases as $case) {
            if ($case->cond === null) {
                $hasDefault = true;

                break;
            }
        }

        if (!$hasDefault) {
            return false;
        }

        // A break leaves the switch and a continue either does the same or
        // leaves an enclosing loop; either way the control flow can end up
        // behind the switch, so the mere presence of one disqualifies it
        $escape = (new NodeFinder)->findFirst($cases, static function (Node $node): bool
        {
            return $node instanceof Node\Stmt\Break_ || $node instanceof Node\Stmt\Continue_;
        });

        if ($escape !== null) {
            return false;
        }

        // Through fallthrough, the control flow of every case that does not
        // terminate on its own reaches the statements of the last case, so
        // only the last case has to terminate
        return $this->alwaysTerminates($cases[array_key_last($cases)]->stmts);
    }

    private function expressionTerminates(Node\Expr $expr): bool
    {
        if ($expr instanceof Node\Expr\Throw_ || $expr instanceof Node\Expr\Exit_) {
            return true;
        }

        if (!$expr instanceof Node\Expr\Match_) {
            return false;
        }

        // Evaluating a match either throws an UnhandledMatchError or evaluates
        // one of its arms; when every arm throws or exits, the match as a
        // whole cannot complete normally
        foreach ($expr->arms as $arm) {
            if (!$this->expressionTerminates($arm->body)) {
                return false;
            }
        }

        return true;
    }

    /**
     * @param array<Node\Stmt> $stmts
     */
    private function infiniteLoopTerminates(array $stmts): bool
    {
        // A break can leave the loop and a goto can jump to a label behind
        // it; whether one actually does is not decided here, so the mere
        // presence of either disqualifies the loop
        $escape = (new NodeFinder)->findFirst($stmts, static function (Node $node): bool
        {
            return $node instanceof Node\Stmt\Break_ || $node instanceof Node\Stmt\Goto_;
        });

        return $escape === null;
    }

    private function forLoopsForever(Node\Stmt\For_ $node): bool
    {
        $conditions = $node->cond;

        if ($conditions === []) {
            return true;
        }

        return $this->isLiteralTrue($conditions[array_key_last($conditions)]);
    }

    private function ifAlwaysTerminates(Node\Stmt\If_ $node): bool
    {
        if ($this->isLiteralTrue($node->cond)) {
            if (!$this->alwaysTerminates($node->stmts)) {
                return false;
            }

            // A goto into any branch can resume the control flow behind this statement
            return !$this->containsLabel([$node]);
        }

        if ($node->else === null) {
            return false;
        }

        if (!$this->alwaysTerminates($node->stmts)) {
            return false;
        }

        foreach ($node->elseifs as $elseif) {
            if (!$this->alwaysTerminates($elseif->stmts)) {
                return false;
            }
        }

        if (!$this->alwaysTerminates($node->else->stmts)) {
            return false;
        }

        // A goto into any branch can resume the control flow behind this statement
        return !$this->containsLabel([$node]);
    }

    /**
     * @param array<Node\Stmt> $stmts
     */
    private function alwaysTerminates(array $stmts): bool
    {
        foreach ($stmts as $stmt) {
            if ($this->isTerminator($stmt)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param array<Node\Stmt> $stmts
     */
    private function containsLabel(array $stmts): bool
    {
        return (new NodeFinder)->findFirstInstanceOf($stmts, Node\Stmt\Label::class) !== null;
    }

    /**
     * @return array<Node\Stmt>
     */
    private function statementsOf(Node $node): array
    {
        if ($node instanceof Node\Stmt\Function_ ||
            $node instanceof Node\Stmt\If_ ||
            $node instanceof Node\Stmt\Else_ ||
            $node instanceof Node\Stmt\ElseIf_ ||
            $node instanceof Node\Stmt\While_ ||
            $node instanceof Node\Stmt\Do_ ||
            $node instanceof Node\Stmt\For_ ||
            $node instanceof Node\Stmt\Foreach_ ||
            $node instanceof Node\Stmt\Case_ ||
            $node instanceof Node\Stmt\Catch_ ||
            $node instanceof Node\Stmt\Finally_ ||
            $node instanceof Node\Stmt\TryCatch ||
            $node instanceof Node\Expr\Closure) {
            return $node->stmts;
        }

        if ($node instanceof Node\Stmt\ClassMethod ||
            $node instanceof Node\Stmt\Namespace_ ||
            $node instanceof Node\Stmt\Declare_) {
            return $node->stmts ?? [];
        }

        return [];
    }

    private function forBodyIsUnreachable(Node\Stmt\For_ $node): bool
    {
        $conditions = $node->cond;

        if ($conditions === []) {
            return false;
        }

        // Only the last of the comma-separated condition expressions
        // determines whether the loop body is entered
        return $this->isLiteralFalse($conditions[array_key_last($conditions)]);
    }

    private function isLiteralTrue(?Node $node): bool
    {
        return $node instanceof Node\Expr\ConstFetch &&
            strtolower($node->name->toString()) === 'true';
    }

    private function isLiteralFalse(?Node $node): bool
    {
        return $node instanceof Node\Expr\ConstFetch &&
            strtolower($node->name->toString()) === 'false';
    }
}
