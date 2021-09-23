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

use function array_merge;
use function range;
use PhpParser\Node;
use PhpParser\Node\Stmt\Class_;
use PhpParser\Node\Stmt\ClassMethod;
use PhpParser\Node\Stmt\Function_;
use PhpParser\Node\Stmt\Interface_;
use PhpParser\Node\Stmt\Trait_;
use PhpParser\NodeTraverser;
use PhpParser\NodeVisitorAbstract;

/**
 * @internal This class is not covered by the backward compatibility promise for phpunit/php-code-coverage
 */
final class IgnoredLinesFindingVisitor extends NodeVisitorAbstract
{
    /**
     * @psalm-var list<int>
     */
    private array $ignoredLines = [];

    private bool $useAnnotationsForIgnoringCode;

    private bool $ignoreDeprecated;

    public function __construct(bool $useAnnotationsForIgnoringCode, bool $ignoreDeprecated)
    {
        $this->useAnnotationsForIgnoringCode = $useAnnotationsForIgnoringCode;
        $this->ignoreDeprecated              = $ignoreDeprecated;
    }

    public function enterNode(Node $node): ?int
    {
        if (!$node instanceof Class_ &&
            !$node instanceof Trait_ &&
            !$node instanceof Interface_ &&
            !$node instanceof ClassMethod &&
            !$node instanceof Function_) {
            return null;
        }

        if ($node instanceof Class_ && $node->isAnonymous()) {
            return null;
        }

        // Workaround for https://bugs.xdebug.org/view.php?id=1798
        if ($node instanceof Class_ ||
            $node instanceof Trait_ ||
            $node instanceof Interface_) {
            $this->ignoredLines[] = $node->getStartLine();
        }

        if (!$this->useAnnotationsForIgnoringCode) {
            return null;
        }

        if ($node instanceof Interface_) {
            return null;
        }

        $docComment = $node->getDocComment();

        if ($docComment === null) {
            return null;
        }

        if (str_contains($docComment->getText(), '@codeCoverageIgnore')) {
            $this->ignoredLines = array_merge(
                $this->ignoredLines,
                range($node->getStartLine(), $node->getEndLine())
            );
        }

        if ($this->ignoreDeprecated && str_contains($docComment->getText(), '@deprecated')) {
            $this->ignoredLines = array_merge(
                $this->ignoredLines,
                range($node->getStartLine(), $node->getEndLine())
            );
        }

        if ($node instanceof ClassMethod || $node instanceof Function_) {
            return NodeTraverser::DONT_TRAVERSE_CHILDREN;
        }

        return null;
    }

    /**
     * @psalm-return list<int>
     */
    public function ignoredLines(): array
    {
        return $this->ignoredLines;
    }
}
