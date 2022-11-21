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
use function max;
use function range;
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
            $this->setLineBranch($node->getStartLine(), $node->getEndLine(), ++$this->nextBranch);

            return;
        }

        if ($node instanceof Node\Stmt\If_ ||
            $node instanceof Node\Stmt\ElseIf_ ||
            $node instanceof Node\Stmt\Else_ ||
            $node instanceof Node\Stmt\Foreach_ ||
            $node instanceof Node\Stmt\While_) {
            if (isset($this->executableLinesGroupedByBranch[$node->getStartLine()])) {
                $stmtBranch = 1 + $this->executableLinesGroupedByBranch[$node->getStartLine()];

                if (false !== array_search($stmtBranch, $this->executableLinesGroupedByBranch, true)) {
                    $stmtBranch = ++$this->nextBranch;
                }
            } else {
                $stmtBranch = ++$this->nextBranch;
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

            if (1 > ($contentEnd - $contentStart)) {
                return;
            }

            $this->setLineBranch(
                $contentStart,
                $contentEnd - 1,
                $stmtBranch
            );

            return;
        }

        if ($node instanceof Node\Stmt\Declare_) {
            $this->unsets[] = range($node->getStartLine(), $node->getEndLine());
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
