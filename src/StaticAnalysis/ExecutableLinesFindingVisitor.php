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

use function array_unique;
use function sort;
use PhpParser\Node;
use PhpParser\Node\Stmt\Break_;
use PhpParser\Node\Stmt\Case_;
use PhpParser\Node\Stmt\Catch_;
use PhpParser\Node\Stmt\Continue_;
use PhpParser\Node\Stmt\Do_;
use PhpParser\Node\Stmt\Echo_;
use PhpParser\Node\Stmt\Else_;
use PhpParser\Node\Stmt\ElseIf_;
use PhpParser\Node\Stmt\Expression;
use PhpParser\Node\Stmt\Finally_;
use PhpParser\Node\Stmt\For_;
use PhpParser\Node\Stmt\Foreach_;
use PhpParser\Node\Stmt\Goto_;
use PhpParser\Node\Stmt\If_;
use PhpParser\Node\Stmt\Return_;
use PhpParser\Node\Stmt\Switch_;
use PhpParser\Node\Stmt\Throw_;
use PhpParser\Node\Stmt\TryCatch;
use PhpParser\Node\Stmt\Unset_;
use PhpParser\Node\Stmt\While_;
use PhpParser\NodeVisitorAbstract;

final class ExecutableLinesFindingVisitor extends NodeVisitorAbstract
{
    /**
     * @psalm-var list<int>
     */
    private $executableLines = [];

    public function enterNode(Node $node): void
    {
        if (!$this->isExecutable($node)) {
            return;
        }

        $this->executableLines[] = $node->getStartLine();
    }

    /**
     * @psalm-return list<int>
     */
    public function executableLines(): array
    {
        $executableLines = array_unique($this->executableLines);

        sort($executableLines);

        return $executableLines;
    }

    private function isExecutable(Node $node): bool
    {
        return $node instanceof Break_ ||
               $node instanceof Case_ ||
               $node instanceof Catch_ ||
               $node instanceof Continue_ ||
               $node instanceof Do_ ||
               $node instanceof Echo_ ||
               $node instanceof ElseIf_ ||
               $node instanceof Else_ ||
               $node instanceof Expression ||
               $node instanceof Finally_ ||
               $node instanceof Foreach_ ||
               $node instanceof For_ ||
               $node instanceof Goto_ ||
               $node instanceof If_ ||
               $node instanceof Return_ ||
               $node instanceof Switch_ ||
               $node instanceof Throw_ ||
               $node instanceof TryCatch ||
               $node instanceof Unset_ ||
               $node instanceof While_;
    }
}
