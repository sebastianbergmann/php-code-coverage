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

use function array_search;
use function current;
use function end;
use function max;
use function range;
use function reset;
use PhpParser\Node;
use PhpParser\NodeVisitorAbstract;

/**
 * @internal This class is not covered by the backward compatibility promise for phpunit/php-code-coverage
 */
final class ExecutableLinesFindingVisitor extends NodeVisitorAbstract
{
    private $nextBranch = 1;

    /**
     * @var array
     */
    private $executableLinesGroupedByBranch = [];

    /**
     * @var array
     */
    private $unsets = [];

    public function enterNode(Node $node): void
    {
        if ($node instanceof Node\Stmt\Class_) {
            return;
        }

        if ($node instanceof Node\Stmt\Function_ ||
            $node instanceof Node\Stmt\ClassMethod
        ) {
            $this->setLineBranch($node->getStartLine(), $node->getEndLine() - 1, ++$this->nextBranch);

            return;
        }

        if ($node instanceof Node\Expr\Ternary) {
            $this->setLineBranch($node->if->getStartLine(), $node->if->getEndLine(), ++$this->nextBranch);
            $this->setLineBranch($node->else->getStartLine(), $node->else->getEndLine(), ++$this->nextBranch);

            return;
        }

        if ($node instanceof Node\Stmt\If_ ||
            $node instanceof Node\Stmt\ElseIf_ ||
            $node instanceof Node\Stmt\Else_ ||
            $node instanceof Node\Stmt\Case_ ||
            $node instanceof Node\Stmt\For_ ||
            $node instanceof Node\Stmt\Foreach_ ||
            $node instanceof Node\Stmt\While_ ||
            $node instanceof Node\Stmt\TryCatch ||
            $node instanceof Node\Stmt\Catch_ ||
            $node instanceof Node\Stmt\Finally_
        ) {
            $incrementNextBranch = false;

            if (isset($this->executableLinesGroupedByBranch[$node->getStartLine()])) {
                $stmtBranch = 1 + $this->executableLinesGroupedByBranch[$node->getStartLine()];

                if (false !== array_search($stmtBranch, $this->executableLinesGroupedByBranch, true)) {
                    $stmtBranch          = 1 + $this->nextBranch;
                    $incrementNextBranch = true;
                }
            } else {
                $stmtBranch          = 1 + $this->nextBranch;
                $incrementNextBranch = true;
            }

            $endLine = $node->getEndLine();

            if ($node instanceof Node\Stmt\If_) {
                if ([] !== $node->elseifs) {
                    $endLine = $node->elseifs[0]->getStartLine();
                } elseif (null !== $node->else) {
                    $endLine = $node->else->getStartLine();
                }
            }

            if (!isset($this->executableLinesGroupedByBranch[$node->getStartLine()])) {
                $this->setLineBranch($node->getStartLine(), $endLine, 1);
            }

            if ([] === $node->stmts) {
                return;
            }

            $contentStart = max(
                $node->getStartLine() + 1,
                $node->stmts[0]->getStartLine()
            );
            $contentEnd = $endLine;

            if (
                $node instanceof Node\Stmt\Catch_ ||
                $node instanceof Node\Stmt\Finally_
            ) {
                $contentStart = $node->getStartLine();
            }

            if ($node instanceof Node\Stmt\Case_) {
                $contentEnd++;
            }

            end($node->stmts);
            $lastNode = current($node->stmts);
            reset($node->stmts);

            if (
                $lastNode instanceof Node\Stmt\Nop ||
                $lastNode instanceof Node\Stmt\Break_
            ) {
                $contentEnd = $lastNode->getEndLine() + 1;
            }

            if (1 > ($contentEnd - $contentStart)) {
                return;
            }

            if ($incrementNextBranch) {
                $this->nextBranch++;
            }

            $this->setLineBranch(
                $contentStart,
                $contentEnd - 1,
                $stmtBranch
            );

            return;
        }

        if ($node instanceof Node\Expr\Match_) {
            foreach ($node->arms as $arm) {
                $this->setLineBranch(
                    $arm->body->getStartLine(),
                    $arm->body->getEndLine(),
                    ++$this->nextBranch
                );
            }

            return;
        }

        if (
            $node instanceof Node\Stmt\Return_ ||
            $node instanceof Node\Stmt\Continue_ ||
            $node instanceof Node\Stmt\Break_ ||
            $node instanceof Node\Stmt\Goto_ ||
            $node instanceof Node\Stmt\Throw_ ||
            $node instanceof Node\Stmt\Label
        ) {
            $returnBranch  = $this->executableLinesGroupedByBranch[$node->getStartLine()];
            $returnEndLine = $node->getEndLine();
            $nextBranch    = null;

            if ($node instanceof Node\Stmt\Label) {
                $returnEndLine = $node->getStartLine() - 1;
            }

            foreach ($this->executableLinesGroupedByBranch as $line => $branch) {
                if ($line <= $returnEndLine || $branch !== $returnBranch) {
                    continue;
                }

                if (null === $nextBranch) {
                    $nextBranch = ++$this->nextBranch;
                }

                $this->executableLinesGroupedByBranch[$line] = $nextBranch;
            }

            return;
        }

        if ($node instanceof Node\Stmt\Declare_) {
            $this->unsets[] = range($node->getStartLine(), $node->getEndLine());

            return;
        }

        if ($node instanceof Node\Identifier) {
            return;
        }

        if (!isset($this->executableLinesGroupedByBranch[$node->getStartLine()])) {
            $this->setLineBranch($node->getStartLine(), $node->getEndLine(), 1);

            return;
        }
    }

    public function afterTraverse(array $nodes): void
    {
        foreach ($this->unsets as $unset) {
            foreach ($unset as $line) {
                unset($this->executableLinesGroupedByBranch[$line]);
            }
        }
    }

    public function executableLinesGroupedByBranch(): array
    {
        return $this->executableLinesGroupedByBranch;
    }

    private function setLineBranch($start, $end, $branch): void
    {
        $this->nextBranch = max($this->nextBranch, $branch);

        foreach (range($start, $end) as $line) {
            $this->executableLinesGroupedByBranch[$line] = $branch;
        }
    }
}
