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
use function array_pop;
use function assert;
use function count;
use function ctype_space;
use function end;
use function explode;
use function is_array;
use function is_string;
use function max;
use function reset;
use function spl_object_id;
use function str_replace;
use function strtolower;
use function trim;
use PhpParser\Node;
use PhpParser\NodeFinder;
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
    private readonly NodeFinder $nodeFinder;

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
     * @var list<array{int, int}> line and file position of the start of a
     *                            match expression that may share its first
     *                            line with other code
     */
    private array $matchStartLines = [];

    /**
     * @var list<array{int, int}> line and file position of the end of a
     *                            match expression that may share its last
     *                            line with other code
     */
    private array $matchEndLines = [];

    /**
     * @var array<int, int> keyed by line number, file position of the start
     *                      of the rightmost node that starts on that line
     */
    private array $rightmostNodeStartOnLine = [];

    /**
     * @var array<int, int> keyed by line number, file position of the end
     *                      of the leftmost node that ends on that line
     */
    private array $leftmostNodeEndOnLine = [];

    /**
     * @var array<int, true> keyed by spl_object_id(), the conditions of the
     *                       arms of match expressions that are compiled to
     *                       a jump table and therefore emit no opcode
     */
    private array $matchConditionsWithoutOpcode = [];

    /**
     * @var array<int, string>
     */
    private array $commentsToCheckForUnset = [];
    private int $spreadDepth               = 0;
    private int $propertyHookDepth         = 0;

    /**
     * Depth of the nesting in expressions that the compiler tries to evaluate
     * before it compiles them: the elements of an array literal, the
     * conditions of the arms of a match expression, and the initializer of a
     * static variable. A constant that is substituted by that evaluation is
     * no longer attributed to its own line, and neither is the opcode of the
     * operator it is an operand of ($b !== null, for instance).
     */
    private int $constantEvaluationDepth = 0;

    /**
     * @var list<int> the depth of the enclosing functions, methods, closures,
     *                and property hooks, which are compiled separately
     */
    private array $constantEvaluationDepthStack = [];

    /**
     * @var array<int, true> keyed by spl_object_id(), the conditions of the
     *                       arms of match expressions
     */
    private array $matchConditions = [];

    /**
     * @var array<int, true> keyed by spl_object_id() of expressions that are
     *                       the value of an array literal element
     */
    private array $arrayItemValues = [];

    /**
     * @var array<int, true> keyed by line number, the lines on which an
     *                       expression statement or a return statement starts
     */
    private array $statementStartLines = [];

    /**
     * @var array<int, true> keyed by spl_object_id() of the property fetches,
     *                       array accesses, and method calls in the operand
     *                       of ??, isset(), and empty(), which PHP may read
     *                       without warnings
     */
    private array $expressionsReadQuietly = [];

    public function __construct(string $source)
    {
        $this->source     = str_replace("\r\n", "\n", $source);
        $this->nodeFinder = new NodeFinder;
    }

    public function enterNode(Node $node): null
    {
        $this->rememberNodePosition($node);

        if ($node instanceof Node\FunctionLike) {
            $this->constantEvaluationDepthStack[] = $this->constantEvaluationDepth;
            $this->constantEvaluationDepth        = 0;
        }

        if ($this->isEvaluatedBeforeItIsCompiled($node)) {
            $this->constantEvaluationDepth++;
        }

        if ($node instanceof Node\ArrayItem) {
            if ($node->unpack) {
                $this->spreadDepth++;
            }

            $this->rememberArrayItemValue($node->value);
        }

        if ($node instanceof Node\PropertyHook) {
            $this->propertyHookDepth++;
        }

        if ($node instanceof Node\Stmt\Expression ||
            $node instanceof Node\Stmt\Return_) {
            $this->statementStartLines[$node->getStartLine()] = true;
        }

        $this->rememberExpressionsReadQuietly($node);

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

        if ($node instanceof Node\Expr\BinaryOp\BooleanAnd ||
            $node instanceof Node\Expr\BinaryOp\LogicalAnd ||
            $node instanceof Node\Expr\BinaryOp\BooleanOr ||
            $node instanceof Node\Expr\BinaryOp\LogicalOr) {
            $this->enterShortCircuitingOperator($node);

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
        if ($this->isEvaluatedBeforeItIsCompiled($node)) {
            $this->constantEvaluationDepth--;
        }

        if ($node instanceof Node\FunctionLike) {
            $this->constantEvaluationDepth = array_pop($this->constantEvaluationDepthStack) ?? 0;
        }

        if ($node instanceof Node\ArrayItem && $node->unpack) {
            $this->spreadDepth--;
        }

        if ($node instanceof Node\PropertyHook) {
            $this->propertyHookDepth--;
        }

        return null;
    }

    public function afterTraverse(array $nodes): null
    {
        $this->stripBlankAndCommentOnlyLines();
        $this->markMatchBoundaryLinesWithoutOtherCodeForUnset();

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
            foreach ($arm->conds ?? [] as $cond) {
                $this->matchConditions[spl_object_id($cond)] = true;
            }

            $this->setLineBranch(
                $arm->body->getStartLine(),
                $arm->body->getEndLine(),
                ++$this->nextBranch,
            );
        }

        if ([] === $node->arms) {
            return;
        }

        if ($this->matchUsesJumpTable($node)) {
            foreach ($node->arms as $arm) {
                foreach ($arm->conds ?? [] as $cond) {
                    $this->matchConditionsWithoutOpcode[spl_object_id($cond)] = true;

                    if ($cond instanceof Node\Expr\UnaryMinus ||
                        $cond instanceof Node\Expr\UnaryPlus) {
                        $this->matchConditionsWithoutOpcode[spl_object_id($cond->expr)] = true;
                    }
                }
            }
        }

        $firstArmLine = reset($node->arms)->getStartLine();
        $lastArmLine  = end($node->arms)->getEndLine();

        // The match expression itself emits no opcode on the lines before its
        // first arm (unless its subject or the MATCH opcode of a jump table
        // does) and on the lines after its last arm, but other code on its
        // first or last line may. Whether there is such code is only known
        // once all nodes have been visited.
        if ($node->getStartLine() < $firstArmLine && !$this->matchOpeningLineAlwaysCarriesOpcode($node)) {
            $this->matchStartLines[] = [$node->getStartLine(), $node->getStartFilePos()];

            $this->markRangeForUnset($node->getStartLine() + 1, $firstArmLine - 1);
        }

        if ($node->getEndLine() > $lastArmLine) {
            $this->matchEndLines[] = [$node->getEndLine(), $node->getEndFilePos()];

            $this->markRangeForUnset($lastArmLine + 1, $node->getEndLine() - 1);
        }
    }

    /**
     * A node that ends on the first line of a match expression before the
     * match expression starts, or that starts on its last line after the
     * match expression ends, is code that shares the line with the match
     * expression. Such a line must not be removed from the executable lines.
     */
    private function markMatchBoundaryLinesWithoutOtherCodeForUnset(): void
    {
        foreach ($this->matchStartLines as [$line, $matchStart]) {
            if (isset($this->leftmostNodeEndOnLine[$line]) && $this->leftmostNodeEndOnLine[$line] < $matchStart) {
                continue;
            }

            $this->unsets[$line] = true;
        }

        foreach ($this->matchEndLines as [$line, $matchEnd]) {
            if (isset($this->rightmostNodeStartOnLine[$line]) && $this->rightmostNodeStartOnLine[$line] > $matchEnd) {
                continue;
            }

            $this->unsets[$line] = true;
        }
    }

    private function rememberNodePosition(Node $node): void
    {
        // A Nop statement only holds comments
        if ($node instanceof Node\Stmt\Nop) {
            return;
        }

        // The condition of an arm of a match expression that is compiled to a
        // jump table is not code that shares its line with a nested match
        // expression: the MATCH opcode is attributed to the line of the
        // enclosing match expression
        if (isset($this->matchConditionsWithoutOpcode[spl_object_id($node)])) {
            return;
        }

        $startLine = $node->getStartLine();
        $endLine   = $node->getEndLine();

        if (!isset($this->rightmostNodeStartOnLine[$startLine]) || $node->getStartFilePos() > $this->rightmostNodeStartOnLine[$startLine]) {
            $this->rightmostNodeStartOnLine[$startLine] = $node->getStartFilePos();
        }

        if (!isset($this->leftmostNodeEndOnLine[$endLine]) || $node->getEndFilePos() < $this->leftmostNodeEndOnLine[$endLine]) {
            $this->leftmostNodeEndOnLine[$endLine] = $node->getEndFilePos();
        }
    }

    /**
     * @param Node\Expr\Closure|Node\Stmt\Class_|Node\Stmt\ClassMethod|Node\Stmt\Enum_|Node\Stmt\Function_|Node\Stmt\Trait_ $node
     */
    private function enterCallableOrClassLike(Node $node): void
    {
        // An abstract method is never executed, not even the expressions of
        // the default values of its parameters
        if ($node instanceof Node\Stmt\ClassMethod && $node->isAbstract()) {
            $this->markRangeForUnset($node->getStartLine(), $node->getEndLine());

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
            if (!is_array($hook->body)) {
                continue;
            }

            foreach ($hook->body as $bodyStmt) {
                if ($bodyStmt instanceof Node\Stmt\Nop) {
                    continue;
                }

                for ($line = $bodyStmt->getStartLine(); $line <= $bodyStmt->getEndLine(); $line++) {
                    $protected[$line] = true;
                }
            }
        }

        return $protected;
    }

    private function enterPropertyHook(Node\PropertyHook $node): void
    {
        $this->markParameterLinesForUnset($node);

        if (!is_array($node->body)) {
            return;
        }

        // The enclosing property declaration was handled by enterDefault(),
        // which assigned one branch to all of its lines, including the bodies
        // of all of its hooks; the statements of a hook body need branches of
        // their own, just like the statements of a method body
        foreach ($node->body as $stmt) {
            for ($line = $stmt->getStartLine(); $line <= $stmt->getEndLine(); $line++) {
                unset($this->executableLinesGroupedByBranch[$line]);
            }
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

        if ($this->isArrayItemValue($node)) {
            if ($node->if !== null) {
                $this->rememberArrayItemValue($node->if);
            }

            $this->rememberArrayItemValue($node->else);

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

        if ($this->isArrayItemValue($node)) {
            $this->rememberArrayItemValue($node->right);

            return;
        }

        $this->setLineBranch(
            $node->getEndLine(),
            $node->getEndLine(),
            ++$this->nextBranch,
        );
    }

    /**
     * The line of the right operand of a && or || that spans multiple lines
     * gets a branch of its own: the right operand is not evaluated when the
     * left operand decides the result, and its line must then not get its
     * status from the other lines of the statement.
     *
     * Whether this has an effect depends on what the driver reports. Xdebug
     * and PCOV only report the line of the last operand of an assignment or
     * of an expression statement as not executed: the line of any other
     * right operand also holds an opcode that is executed when the operand is
     * not evaluated (the jump of the operator that follows, or the opcode
     * that returns the result). PCOV reports more of these lines as not
     * executed when the optimizer of OPcache is enabled.
     *
     * The grouping is kept wherever the line of an operand may not carry an
     * opcode of its own, as that line could then no longer get its status
     * from the other lines of the statement.
     */
    private function enterShortCircuitingOperator(Node\Expr\BinaryOp $node): void
    {
        $this->enterDefault($node);

        if ($this->spreadDepth > 0 || $this->constantEvaluationDepth > 0) {
            return;
        }

        $line     = $node->right->getStartLine();
        $leftLine = $node->left->getEndLine();

        if ($line <= $leftLine ||
            $line !== $node->right->getEndLine() ||
            !isset($this->executableLinesGroupedByBranch[$line], $this->executableLinesGroupedByBranch[$leftLine])) {
            return;
        }

        // The line of the right operand may be the first line of a range of
        // lines that was grouped before (the body of an arrow function, an
        // arm of a ternary operator or of a match expression): the other
        // lines of that range can only get their status from that line
        if ($this->executableLinesGroupedByBranch[$line] !== $this->executableLinesGroupedByBranch[$leftLine]) {
            return;
        }

        if (!$this->containsVariable($node->left) ||
            !$this->containsVariable($node->right)) {
            return;
        }

        if (($node instanceof Node\Expr\BinaryOp\BooleanOr || $node instanceof Node\Expr\BinaryOp\LogicalOr) &&
            !$this->lineOfOperandBeforeJumpHasStatusOfItsOwn($node->left)) {
            return;
        }

        $this->executableLinesGroupedByBranch[$line] = ++$this->nextBranch;
    }

    /**
     * Xdebug does not report every opcode as executed: not the jump of ||
     * (JMPNZ_EX) and of the nullsafe operator (JMP_NULL), not the opcodes of
     * isset(), a static property, and clone, and not the opcodes of a
     * property fetch or an array access that is read without warnings. A
     * variable is not compiled to an opcode of its own.
     *
     * The line of the left operand of || on which no opcode is executed that
     * Xdebug reports can only get its status from the line of the right
     * operand: PHP attributes the opcode that uses the value of the
     * expression to that line, and that opcode is always executed.
     *
     * Such a line has a status of its own when a statement starts on it, or
     * when the operand is compiled to an opcode that is executed whenever
     * the line is reached and that Xdebug and PCOV report. The jump of &&
     * (JMPZ_EX) is reported.
     */
    private function lineOfOperandBeforeJumpHasStatusOfItsOwn(Node\Expr $operand): bool
    {
        $line = $operand->getEndLine();

        if (isset($this->statementStartLines[$line])) {
            return true;
        }

        // The left operand of || is evaluated whenever the || is
        if ($operand instanceof Node\Expr\BinaryOp\BooleanOr ||
            $operand instanceof Node\Expr\BinaryOp\LogicalOr) {
            return $this->lineOfOperandBeforeJumpHasStatusOfItsOwn($operand->left);
        }

        if ($operand instanceof Node\Expr\MethodCall ||
            $operand instanceof Node\Expr\NullsafeMethodCall ||
            $operand instanceof Node\Expr\PropertyFetch ||
            $operand instanceof Node\Expr\NullsafePropertyFetch ||
            $operand instanceof Node\Expr\ArrayDimFetch) {
            return $this->lineOfChainHasStatusOfItsOwn($operand, $line);
        }

        return $operand instanceof Node\Expr\FuncCall ||
               $operand instanceof Node\Expr\StaticCall ||
               $operand instanceof Node\Expr\New_ ||
               $operand instanceof Node\Expr\Assign ||
               $operand instanceof Node\Expr\BooleanNot ||
               $operand instanceof Node\Expr\Instanceof_ ||
               $operand instanceof Node\Expr\Ternary ||
               $operand instanceof Node\Expr\Cast\Bool_ ||
               $operand instanceof Node\Expr\BinaryOp\BooleanAnd ||
               $operand instanceof Node\Expr\BinaryOp\LogicalAnd ||
               $operand instanceof Node\Expr\BinaryOp\Coalesce ||
               $operand instanceof Node\Expr\BinaryOp\Identical ||
               $operand instanceof Node\Expr\BinaryOp\NotIdentical ||
               $operand instanceof Node\Expr\BinaryOp\Equal ||
               $operand instanceof Node\Expr\BinaryOp\NotEqual ||
               $operand instanceof Node\Expr\BinaryOp\Smaller ||
               $operand instanceof Node\Expr\BinaryOp\SmallerOrEqual ||
               $operand instanceof Node\Expr\BinaryOp\Greater ||
               $operand instanceof Node\Expr\BinaryOp\GreaterOrEqual;
    }

    /**
     * A method call, a property fetch, and an array access are compiled to
     * an opcode that Xdebug reports, unless they are read without warnings.
     * That opcode is not executed when a nullsafe operator before it in the
     * chain skips the rest of the chain. When the object of that nullsafe
     * operator ends on the same line, the line is reached nevertheless, and
     * only has a status of its own when that object has.
     *
     * @param Node\Expr\ArrayDimFetch|Node\Expr\MethodCall|Node\Expr\NullsafeMethodCall|Node\Expr\NullsafePropertyFetch|Node\Expr\PropertyFetch $chain
     */
    private function lineOfChainHasStatusOfItsOwn(Node\Expr $chain, int $line): bool
    {
        if (isset($this->expressionsReadQuietly[spl_object_id($chain)])) {
            return false;
        }

        $expr = $chain;

        while ($expr instanceof Node\Expr\MethodCall ||
               $expr instanceof Node\Expr\NullsafeMethodCall ||
               $expr instanceof Node\Expr\PropertyFetch ||
               $expr instanceof Node\Expr\NullsafePropertyFetch ||
               $expr instanceof Node\Expr\ArrayDimFetch) {
            if (($expr instanceof Node\Expr\NullsafeMethodCall || $expr instanceof Node\Expr\NullsafePropertyFetch) &&
                $expr->var->getEndLine() === $line) {
                return $this->lineOfOperandBeforeJumpHasStatusOfItsOwn($expr->var);
            }

            $expr = $expr->var;
        }

        return true;
    }

    /**
     * PHP reads the operand of ??, isset(), and empty() without warnings for
     * an undefined variable, property, or array element: a property fetch or
     * an array access in such an operand is compiled to another opcode.
     */
    private function rememberExpressionsReadQuietly(Node $node): void
    {
        $expressions = [];

        if ($node instanceof Node\Expr\BinaryOp\Coalesce) {
            $expressions[] = $node->left;
        } elseif ($node instanceof Node\Expr\AssignOp\Coalesce) {
            $expressions[] = $node->var;
        } elseif ($node instanceof Node\Expr\Isset_) {
            $expressions = $node->vars;
        } elseif ($node instanceof Node\Expr\Empty_) {
            $expressions[] = $node->expr;
        }

        foreach ($expressions as $expr) {
            while ($expr instanceof Node\Expr\MethodCall ||
                   $expr instanceof Node\Expr\NullsafeMethodCall ||
                   $expr instanceof Node\Expr\PropertyFetch ||
                   $expr instanceof Node\Expr\NullsafePropertyFetch ||
                   $expr instanceof Node\Expr\ArrayDimFetch) {
                $this->expressionsReadQuietly[spl_object_id($expr)] = true;

                $expr = $expr->var;
            }
        }
    }

    /**
     * An expression that does not contain a variable may be evaluated when
     * the file is compiled: the compiler substitutes constants and folds
     * operators that are applied to them (\PHP_VERSION_ID >= 80100, -1, ...),
     * and the optimizer of OPcache evaluates calls such as
     * \function_exists('name') and \defined('NAME'). The line of such an
     * expression then carries no opcode and can only get its status from
     * another line of its branch.
     */
    private function containsVariable(Node\Expr $expr): bool
    {
        return null !== $this->nodeFinder->findFirst(
            $expr,
            static fn (Node $node): bool => $node instanceof Node\Expr\Variable ||
                                            $node instanceof Node\Expr\StaticPropertyFetch,
        );
    }

    /**
     * @param Node\Stmt\ElseIf_|Node\Stmt\If_ $node
     */
    private function enterConditional(Node $node): void
    {
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

            // Drivers that cannot see the inside of property hooks (PCOV
            // 1.0.12 and earlier, for instance) report no line of a hook
            // body at all; forcing such lines to "not executed" would make
            // them permanently red
            if ($this->propertyHookDepth === 0) {
                $this->branchOperatorLines[$line] = true;
            }
        }
    }

    private function isEvaluatedBeforeItIsCompiled(Node $node): bool
    {
        return $node instanceof Node\ArrayItem ||
               $node instanceof Node\StaticVar ||
               isset($this->matchConditions[spl_object_id($node)]);
    }

    private function rememberArrayItemValue(Node\Expr $node): void
    {
        $this->arrayItemValues[spl_object_id($node)] = true;
    }

    private function isArrayItemValue(Node\Expr $node): bool
    {
        return isset($this->arrayItemValues[spl_object_id($node)]);
    }

    /**
     * Mirrors zend_compile_match(): a match expression with at least two
     * conditions that all are int or string constants is compiled to a
     * single MATCH opcode with a jump table instead of one comparison per
     * condition.
     */
    private function matchUsesJumpTable(Node\Expr\Match_ $node): bool
    {
        $numberOfConditions = 0;

        foreach ($node->arms as $arm) {
            foreach ($arm->conds ?? [] as $cond) {
                if (!$this->isIntOrStringConstant($cond)) {
                    return false;
                }

                $numberOfConditions++;
            }
        }

        return $numberOfConditions >= 2;
    }

    /**
     * Of the expressions that the Zend compiler only turns into a constant by
     * constant folding (-1, 'a' . 'b', ...), only a signed integer literal is
     * recognized here.
     */
    private function isIntOrStringConstant(Node\Expr $expr): bool
    {
        if ($expr instanceof Node\Expr\UnaryMinus ||
            $expr instanceof Node\Expr\UnaryPlus) {
            return $expr->expr instanceof Node\Scalar\Int_;
        }

        return $expr instanceof Node\Scalar\Int_ ||
               $expr instanceof Node\Scalar\String_;
    }

    /**
     * The opening line of a match expression carries an opcode when its
     * subject compiles to one, or when the match expression is compiled to a
     * jump table: the MATCH opcode is then attributed to that line.
     *
     * Whether a match expression with conditions such as class constants is
     * compiled to a jump table depends on the compiler (OPcache does not
     * substitute such constants) and on whether the class was already loaded
     * when the file was compiled. Treating the opening line as executable only
     * when it carries an opcode however the file was compiled keeps coverage
     * data of runs that compiled the file differently consistent.
     */
    private function matchOpeningLineAlwaysCarriesOpcode(Node\Expr\Match_ $node): bool
    {
        return !$this->matchSubjectMayHaveNoOpcode($node->cond) ||
               $this->matchUsesJumpTable($node);
    }

    /**
     * A compiled variable, a literal, and a constant that the compiler
     * substitutes do not compile to an opcode of their own: they are operands
     * of the opcodes that use them.
     */
    private function matchSubjectMayHaveNoOpcode(Node\Expr $subject): bool
    {
        if ($subject instanceof Node\Expr\Variable) {
            return is_string($subject->name) && $subject->name !== 'this';
        }

        return $subject instanceof Node\Expr\ConstFetch ||
               $subject instanceof Node\Expr\ClassConstFetch ||
               $subject instanceof Node\Scalar\Int_ ||
               $subject instanceof Node\Scalar\Float_ ||
               $subject instanceof Node\Scalar\String_;
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
