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

use function array_any;
use function array_diff_key;
use function array_intersect_key;
use function assert;
use function count;
use function ctype_space;
use function end;
use function explode;
use function is_array;
use function max;
use function reset;
use function str_replace;
use function strtolower;
use function trim;
use PhpParser\Node;
use PhpParser\NodeVisitorAbstract;

/**
 * Identifies lines of a parsed PHP source file that should appear in the code
 * coverage report and groups them by execution branch.
 *
 * Two related sets are produced and exposed via accessors:
 *
 *  - {@see executableLinesGroupedByBranch()}:
 *      An over-approximating envelope. Every line that may potentially carry
 *      an opcode reachable by the runtime is included, mapped to a branch
 *      identifier. The filter pipeline keeps driver hits for any of these
 *      lines and lets sibling lines in the same branch inherit each other's
 *      execution status.
 *
 *  - {@see branchOperatorLines()}:
 *      A precise subset. Only lines added by handlers for AST nodes whose
 *      execution is guaranteed to emit an opcode (match arm bodies, ternary
 *      branches, coalesce right-hand side, conditional and loop conditions,
 *      function-call sites, arrow-function bodies, …) are included. Lines
 *      contributed only by the generic statement default ("anything else")
 *      are excluded. The filter pipeline force-marks lines from this set as
 *      not-executed when the driver did not report them, so reachable but
 *      un-hit branches still surface in the report; structural default-only
 *      lines are spared this treatment.
 *
 * After traversal, two cleanup passes run in {@see afterTraverse()}:
 *
 *  - Blank lines and lines whose only content matches a tracked comment are
 *    removed from the executable map.
 *  - The {@see $unsets} map is applied destructively to the executable map,
 *    and {@see $branchOperatorLines} is intersected with the result so it
 *    cannot reference lines no longer in the executable map.
 *
 * IMPORTANT: Behaviour changes here must be accompanied by a {@see Version::id()}
 *            bump. Otherwise stale entries in {@see CachingSourceAnalyser}'s
 *            on-disk cache will serve incorrect analysis results.
 *
 * @internal This class is not covered by the backward compatibility promise for phpunit/php-code-coverage
 *
 * @no-named-arguments Parameter names are not covered by the backward compatibility promise for phpunit/php-code-coverage
 *
 * @phpstan-import-type LinesType from AnalysisResult
 */
final class ExecutableLinesFindingVisitor extends NodeVisitorAbstract
{
    /**
     * @var list<class-string<Node>>
     */
    private const array ALWAYS_IGNORED = [
        Node\Stmt\Declare_::class,
        Node\DeclareItem::class,
        Node\Stmt\Else_::class,
        Node\Stmt\EnumCase::class,
        Node\Stmt\Finally_::class,
        Node\Stmt\GroupUse::class,
        Node\Stmt\Label::class,
        Node\Stmt\Namespace_::class,
        Node\Stmt\Nop::class,
        Node\Stmt\Switch_::class,
        Node\Stmt\TryCatch::class,
        Node\Stmt\Use_::class,
        Node\UseItem::class,
        Node\Expr\ConstFetch::class,
        Node\Expr\Variable::class,
        Node\Expr\Throw_::class,
        Node\ComplexType::class,
        Node\Const_::class,
        Node\Identifier::class,
        Node\Name::class,
        Node\Param::class,
        Node\Scalar::class,
    ];
    private int $nextBranch = 0;
    private readonly string $source;

    /**
     * @var LinesType
     */
    private array $executableLinesGroupedByBranch = [];

    /**
     * @var array<positive-int, true>
     */
    private array $branchOperatorLines = [];

    /**
     * @var array<int, bool>
     */
    private array $unsets = [];

    /**
     * @var array<int, string>
     */
    private array $commentsToCheckForUnset = [];
    private int $spreadDepth               = 0;

    public function __construct(string $source)
    {
        $this->source = str_replace("\r\n", "\n", $source);
    }

    public function enterNode(Node $node): null
    {
        if ($node instanceof Node\ArrayItem && $node->unpack) {
            $this->spreadDepth++;
        }

        $this->collectMatchingComments($node);

        if ($node instanceof Node\Scalar\String_ ||
            $node instanceof Node\InterpolatedStringPart) {
            $this->stripMultilineStringInterior($node);

            return null;
        }

        if ($node instanceof Node\Stmt\Interface_ ||
            $node instanceof Node\Attribute) {
            $this->markRangeForUnset($node->getStartLine(), $node->getEndLine());

            return null;
        }

        if ($this->isAlwaysIgnored($node)) {
            return null;
        }

        if ($node instanceof Node\Expr\Match_) {
            $this->enterMatch($node);

            return null;
        }

        if ($node instanceof Node\Stmt\Expression &&
            $node->expr instanceof Node\Expr\Throw_) {
            $line = $node->expr->expr->getEndLine();
            $this->setLineBranch($line, $line, ++$this->nextBranch);

            return null;
        }

        if ($node instanceof Node\Stmt\Expression &&
            $this->isSideEffectFreeExpressionStatement($node)) {
            return null;
        }

        if ($node instanceof Node\Stmt\Enum_ ||
            $node instanceof Node\Stmt\Function_ ||
            $node instanceof Node\Stmt\Class_ ||
            $node instanceof Node\Stmt\ClassMethod ||
            $node instanceof Node\Expr\Closure ||
            $node instanceof Node\Stmt\Trait_) {
            $this->enterCallableOrClassLike($node);

            return null;
        }

        if ($node instanceof Node\PropertyHook) {
            $this->enterPropertyHook($node);

            return null;
        }

        if ($node instanceof Node\Expr\ArrowFunction) {
            $this->enterArrowFunction($node);

            return null;
        }

        if ($node instanceof Node\Expr\Ternary) {
            $this->enterTernary($node);

            return null;
        }

        if ($node instanceof Node\Expr\BinaryOp\Coalesce) {
            $this->enterCoalesce($node);

            return null;
        }

        if ($node instanceof Node\Stmt\If_ ||
            $node instanceof Node\Stmt\ElseIf_) {
            $this->enterConditional($node);

            return null;
        }

        if ($node instanceof Node\Stmt\Case_) {
            $this->enterCase($node);

            return null;
        }

        if ($node instanceof Node\Stmt\For_) {
            $this->enterFor($node);

            return null;
        }

        if ($node instanceof Node\Stmt\Foreach_) {
            $this->setLineBranch(
                $node->expr->getStartLine(),
                $node->valueVar->getEndLine(),
                ++$this->nextBranch,
            );

            return null;
        }

        if ($node instanceof Node\Stmt\While_ ||
            $node instanceof Node\Stmt\Do_) {
            $this->setLineBranch(
                $node->cond->getStartLine(),
                $node->cond->getEndLine(),
                ++$this->nextBranch,
            );

            return null;
        }

        if ($node instanceof Node\Stmt\Catch_) {
            $this->enterCatch($node);

            return null;
        }

        if ($node instanceof Node\Expr\CallLike) {
            $branch = $this->executableLinesGroupedByBranch[$node->getStartLine()] ?? ++$this->nextBranch;

            $this->setLineBranch($node->getStartLine(), $node->getEndLine(), $branch);

            return null;
        }

        $this->enterDefault($node);

        return null;
    }

    public function leaveNode(Node $node): null
    {
        if ($node instanceof Node\ArrayItem && $node->unpack) {
            $this->spreadDepth--;
        }

        return null;
    }

    public function afterTraverse(array $nodes): null
    {
        $this->stripBlankAndCommentOnlyLines();

        $this->executableLinesGroupedByBranch = array_diff_key(
            $this->executableLinesGroupedByBranch,
            $this->unsets,
        );

        $this->branchOperatorLines = array_intersect_key(
            $this->branchOperatorLines,
            $this->executableLinesGroupedByBranch,
        );

        return null;
    }

    /**
     * @return LinesType
     */
    public function executableLinesGroupedByBranch(): array
    {
        return $this->executableLinesGroupedByBranch;
    }

    /**
     * @return array<positive-int, true>
     */
    public function branchOperatorLines(): array
    {
        return $this->branchOperatorLines;
    }

    private function collectMatchingComments(Node $node): void
    {
        foreach ($node->getComments() as $comment) {
            $commentLine = $comment->getStartLine();

            if (!isset($this->executableLinesGroupedByBranch[$commentLine])) {
                continue;
            }

            foreach (explode("\n", $comment->getText()) as $text) {
                $this->commentsToCheckForUnset[$commentLine] = $text;

                $commentLine++;
            }
        }
    }

    private function stripMultilineStringInterior(Node $node): void
    {
        $startLine = $node->getStartLine() + 1;
        $endLine   = $node->getEndLine() - 1;

        for ($line = $startLine; $line <= $endLine; $line++) {
            unset($this->executableLinesGroupedByBranch[$line]);
        }
    }

    private function isAlwaysIgnored(Node $node): bool
    {
        return array_any(
            self::ALWAYS_IGNORED,
            /** class-string<Node> $class */
            static fn (string $class) => $node instanceof $class,
        );
    }

    private function enterMatch(Node\Expr\Match_ $node): void
    {
        foreach ($node->arms as $arm) {
            $this->setLineBranch(
                $arm->body->getStartLine(),
                $arm->body->getEndLine(),
                ++$this->nextBranch,
            );
        }

        if ([] === $node->arms) {
            return;
        }

        $firstArmLine = reset($node->arms)->getStartLine();
        $lastArmLine  = end($node->arms)->getEndLine();

        if ($node->getStartLine() < $firstArmLine && $this->matchConditionHasNoOpcode($node->cond)) {
            $this->markRangeForUnset($node->getStartLine(), $firstArmLine - 1);
        }

        if ($node->getEndLine() > $lastArmLine) {
            $this->markRangeForUnset($lastArmLine + 1, $node->getEndLine());
        }
    }

    /**
     * @param Node\Expr\Closure|Node\Stmt\Class_|Node\Stmt\ClassMethod|Node\Stmt\Enum_|Node\Stmt\Function_|Node\Stmt\Trait_ $node
     */
    private function enterCallableOrClassLike(Node $node): void
    {
        if ($node instanceof Node\Stmt\ClassMethod && $node->isAbstract()) {
            return;
        }

        if ($node instanceof Node\Stmt\Function_ || $node instanceof Node\Stmt\ClassMethod) {
            $this->markParameterLinesForUnset($node);
        }

        $isConcreteClassLike = $node instanceof Node\Stmt\Enum_ ||
            $node instanceof Node\Stmt\Class_ ||
            $node instanceof Node\Stmt\Trait_;

        if (null !== $node->stmts) {
            foreach ($node->stmts as $stmt) {
                if ($stmt instanceof Node\Stmt\Nop) {
                    continue;
                }

                $protectedLines = $this->linesProtectedFromClassBodyUnset($stmt);

                for ($line = $stmt->getStartLine(); $line <= $stmt->getEndLine(); $line++) {
                    if (isset($protectedLines[$line])) {
                        continue;
                    }

                    unset(
                        $this->executableLinesGroupedByBranch[$line],
                        $this->branchOperatorLines[$line],
                    );

                    if ($isConcreteClassLike && !$stmt instanceof Node\Stmt\ClassMethod) {
                        $this->unsets[$line] = true;
                    }
                }
            }
        }

        if ($isConcreteClassLike) {
            return;
        }

        $hasEmptyBody = [] === $node->stmts ||
            null === $node->stmts ||
            (
                1 === count($node->stmts) &&
                reset($node->stmts) instanceof Node\Stmt\Nop
            );

        if (!$hasEmptyBody) {
            return;
        }

        if ($node->getEndLine() === $node->getStartLine() &&
            isset($this->executableLinesGroupedByBranch[$node->getStartLine()])) {
            return;
        }

        $this->setLineBranch($node->getEndLine(), $node->getEndLine(), ++$this->nextBranch);
    }

    /**
     * Lines belonging to property-hook bodies must not be unset by the
     * enclosing class iteration; the {@see enterPropertyHook()} handler will
     * mark them, and traversal of inner statements will populate them via
     * normal handlers.
     *
     * @return array<int, true>
     */
    private function linesProtectedFromClassBodyUnset(Node\Stmt $stmt): array
    {
        if (!$stmt instanceof Node\Stmt\Property) {
            return [];
        }

        $protected = [];

        foreach ($stmt->hooks as $hook) {
            if ($hook->body === null) {
                continue;
            }

            if ($hook->body instanceof Node\Expr) {
                for ($line = $hook->body->getStartLine(); $line <= $hook->body->getEndLine(); $line++) {
                    $protected[$line] = true;
                }

                continue;
            }

            if (is_array($hook->body)) {
                foreach ($hook->body as $bodyStmt) {
                    if ($bodyStmt instanceof Node\Stmt\Nop) {
                        continue;
                    }

                    for ($line = $bodyStmt->getStartLine(); $line <= $bodyStmt->getEndLine(); $line++) {
                        $protected[$line] = true;
                    }
                }
            }
        }

        return $protected;
    }

    private function enterPropertyHook(Node\PropertyHook $node): void
    {
        $this->markParameterLinesForUnset($node);

        if ($node->body === null) {
            return;
        }

        if ($node->body instanceof Node\Expr) {
            $this->setLineBranch(
                $node->body->getStartLine(),
                $node->body->getEndLine(),
                ++$this->nextBranch,
            );
        }
    }

    private function enterArrowFunction(Node\Expr\ArrowFunction $node): void
    {
        $startLine = max(
            $node->getStartLine() + 1,
            $node->expr->getStartLine(),
        );

        $endLine = $node->expr->getEndLine();

        if ($endLine < $startLine) {
            return;
        }

        $this->setLineBranch($startLine, $endLine, ++$this->nextBranch);
    }

    private function enterTernary(Node\Expr\Ternary $node): void
    {
        if ($this->spreadDepth > 0) {
            return;
        }

        if (null !== $node->if &&
            $node->getStartLine() !== $node->if->getEndLine()) {
            $this->setLineBranch(
                $node->if->getStartLine(),
                $node->if->getEndLine(),
                ++$this->nextBranch,
            );
        }

        if ($node->getStartLine() !== $node->else->getEndLine()) {
            $this->setLineBranch(
                $node->else->getStartLine(),
                $node->else->getEndLine(),
                ++$this->nextBranch,
            );
        }
    }

    private function enterCoalesce(Node\Expr\BinaryOp\Coalesce $node): void
    {
        if ($node->getStartLine() === $node->getEndLine()) {
            return;
        }

        $this->setLineBranch(
            $node->getEndLine(),
            $node->getEndLine(),
            ++$this->nextBranch,
        );
    }

    /**
     * @param Node\Stmt\ElseIf_|Node\Stmt\If_ $node
     */
    private function enterConditional(Node $node): void
    {
        if (null === $node->cond) {
            return;
        }

        $this->setLineBranch(
            $node->cond->getStartLine(),
            $node->cond->getStartLine(),
            ++$this->nextBranch,
        );
    }

    private function enterCase(Node\Stmt\Case_ $node): void
    {
        if (null === $node->cond) {
            return;
        }

        $line = max(1, $node->cond->getStartLine());

        $this->executableLinesGroupedByBranch[$line] = ++$this->nextBranch;
    }

    private function enterFor(Node\Stmt\For_ $node): void
    {
        $startLine = null;
        $endLine   = null;

        if ([] !== $node->init) {
            $startLine = reset($node->init)->getStartLine();
            $endLine   = end($node->init)->getEndLine();
        }

        if ([] !== $node->cond) {
            $startLine ??= reset($node->cond)->getStartLine();
            $endLine = end($node->cond)->getEndLine();
        }

        if ([] !== $node->loop) {
            $startLine ??= reset($node->loop)->getStartLine();
            $endLine = end($node->loop)->getEndLine();
        }

        if (null === $startLine || null === $endLine) {
            return;
        }

        $this->setLineBranch($startLine, $endLine, ++$this->nextBranch);
    }

    private function enterCatch(Node\Stmt\Catch_ $node): void
    {
        assert([] !== $node->types);

        $startLine = reset($node->types)->getStartLine();
        $endLine   = end($node->types)->getEndLine();

        $this->setLineBranch($startLine, $endLine, ++$this->nextBranch);
    }

    private function enterDefault(Node $node): void
    {
        if (isset($this->executableLinesGroupedByBranch[$node->getStartLine()])) {
            return;
        }

        $branch = ++$this->nextBranch;

        for ($line = max(1, $node->getStartLine()); $line <= $node->getEndLine(); $line++) {
            $this->executableLinesGroupedByBranch[$line] = $branch;
        }
    }

    /**
     * @param Node\PropertyHook|Node\Stmt\ClassMethod|Node\Stmt\Function_ $node
     */
    private function markParameterLinesForUnset(Node $node): void
    {
        $unsets = [];

        foreach ($node->getParams() as $param) {
            for ($line = $param->getStartLine(); $line <= $param->getEndLine(); $line++) {
                $unsets[$line] = true;
            }
        }

        unset($unsets[$node->getEndLine()]);

        $this->unsets += $unsets;
    }

    private function markRangeForUnset(int $start, int $end): void
    {
        for ($line = $start; $line <= $end; $line++) {
            $this->unsets[$line] = true;
        }
    }

    /**
     * Marks every line in `[$start, $end]` as executable in branch `$branch`
     * AND records each line as branch-operator-derived (i.e. the line was
     * added by a specific node handler, not by the generic default).
     *
     * Use this for any line that the analyser believes is guaranteed to carry
     * an opcode the driver can hit. Anything that should NOT enter the
     * branch-operator set (for example, the generic statement default) must
     * write to {@see $executableLinesGroupedByBranch} directly.
     */
    private function setLineBranch(int $start, int $end, int $branch): void
    {
        for ($line = max(1, $start); $line <= $end; $line++) {
            $this->executableLinesGroupedByBranch[$line] = $branch;
            $this->branchOperatorLines[$line]            = true;
        }
    }

    private function matchConditionHasNoOpcode(Node\Expr $cond): bool
    {
        if (!$cond instanceof Node\Expr\ConstFetch) {
            return false;
        }

        $name = strtolower($cond->name->toString());

        return $name === 'true' || $name === 'false' || $name === 'null';
    }

    /**
     * A statement consisting solely of a literal scalar (`'foo';`, `42;`) or
     * one of the named constants `true`, `false`, `null` produces no opcodes:
     * the Zend compiler discards it as dead code, so the driver cannot report
     * it as executed and the analyser must not record it as executable.
     */
    private function isSideEffectFreeExpressionStatement(Node\Stmt\Expression $node): bool
    {
        $expr = $node->expr;

        if ($expr instanceof Node\Scalar\String_ ||
            $expr instanceof Node\Scalar\Int_ ||
            $expr instanceof Node\Scalar\Float_) {
            return true;
        }

        if ($expr instanceof Node\Expr\ConstFetch) {
            $name = strtolower($expr->name->toString());

            return $name === 'true' || $name === 'false' || $name === 'null';
        }

        return false;
    }

    private function stripBlankAndCommentOnlyLines(): void
    {
        foreach (explode("\n", $this->source) as $i => $line) {
            $lineNumber = $i + 1;

            if ($line === '' || ctype_space($line)) {
                unset($this->executableLinesGroupedByBranch[$lineNumber]);

                continue;
            }

            if (isset($this->commentsToCheckForUnset[$lineNumber]) &&
                trim($line) === trim($this->commentsToCheckForUnset[$lineNumber])) {
                unset($this->executableLinesGroupedByBranch[$lineNumber]);
            }
        }
    }
}
