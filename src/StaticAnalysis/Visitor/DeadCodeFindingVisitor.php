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

use function ksort;
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
 *  - Bodies of branches with literal-constant conditions: `if (false) { ... }`,
 *    `elseif (false) { ... }`, `while (false) { ... }`, `for (...; false; ...) { ... }`,
 *    the `elseif`/`else` tail after an `if (true)`, and the unreachable arm of
 *    a ternary with a literal-constant condition.
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

        foreach ($node->elseifs as $elseif) {
            if ($this->containsLabel($elseif->stmts)) {
                continue;
            }

            $this->markRange($elseif->getStartLine(), $elseif->getEndLine());
        }

        if ($node->else !== null && !$this->containsLabel($node->else->stmts)) {
            $this->markRange($node->else->getStartLine(), $node->else->getEndLine());
        }
    }

    private function handleTernary(Node\Expr\Ternary $node): void
    {
        if ($node->getStartLine() === $node->getEndLine()) {
            return;
        }

        if ($this->isLiteralTrue($node->cond)) {
            $this->markRange($node->else->getStartLine(), $node->else->getEndLine());

            return;
        }

        if ($this->isLiteralFalse($node->cond) && $node->if !== null) {
            $this->markRange($node->if->getStartLine(), $node->if->getEndLine());
        }
    }

    /**
     * @param array<Node\Stmt> $stmts
     */
    private function markStatementsAfterTerminator(array $stmts): void
    {
        $terminated = false;

        foreach ($stmts as $stmt) {
            if (!$terminated) {
                if ($this->isTerminator($stmt)) {
                    $terminated = true;
                }

                continue;
            }

            if ($this->containsLabel([$stmt])) {
                $terminated = false;

                continue;
            }

            if (!$stmt instanceof Node\Stmt\Nop) {
                $this->markRange($stmt->getStartLine(), $stmt->getEndLine());
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
            return $stmt->expr instanceof Node\Expr\Throw_ ||
                $stmt->expr instanceof Node\Expr\Exit_;
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
        if ($node->cond === []) {
            return false;
        }

        foreach ($node->cond as $condition) {
            if ($this->isLiteralFalse($condition)) {
                return true;
            }
        }

        return false;
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
