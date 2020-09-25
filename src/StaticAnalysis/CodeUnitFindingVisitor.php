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

use function implode;
use function rtrim;
use function trim;
use PhpParser\Node;
use PhpParser\Node\Identifier;
use PhpParser\Node\Name;
use PhpParser\Node\NullableType;
use PhpParser\Node\Stmt\Class_;
use PhpParser\Node\Stmt\ClassMethod;
use PhpParser\Node\Stmt\Function_;
use PhpParser\Node\Stmt\Interface_;
use PhpParser\Node\Stmt\Trait_;
use PhpParser\Node\UnionType;
use PhpParser\NodeTraverser;
use PhpParser\NodeVisitorAbstract;
use SebastianBergmann\Complexity\CyclomaticComplexityCalculatingVisitor;

/**
 * @internal This class is not covered by the backward compatibility promise for phpunit/php-code-coverage
 */
final class CodeUnitFindingVisitor extends NodeVisitorAbstract
{
    /**
     * @var array
     */
    private $classes = [];

    /**
     * @var array
     */
    private $traits = [];

    /**
     * @var array
     */
    private $functions = [];

    public function enterNode(Node $node)
    {
        if ($node instanceof Class_) {
            if ($node->isAnonymous()) {
                return;
            }

            $this->processClass($node);
        }

        if ($node instanceof Trait_) {
            $this->processTrait($node);
        }

        if (!$node instanceof ClassMethod && !$node instanceof Function_) {
            return null;
        }

        if ($node instanceof ClassMethod) {
            $parentNode = $node->getAttribute('parent');

            if ($parentNode instanceof Class_ && $parentNode->isAnonymous()) {
                return;
            }

            $this->processMethod($node);

            return;
        }

        $this->processFunction($node);
    }

    public function classes(): array
    {
        return $this->classes;
    }

    public function traits(): array
    {
        return $this->traits;
    }

    public function functions(): array
    {
        return $this->functions;
    }

    /**
     * @psalm-param ClassMethod|Function_ $node
     */
    private function cyclomaticComplexity(Node $node): int
    {
        assert($node instanceof ClassMethod || $node instanceof Function_);

        $nodes = $node->getStmts();

        if ($nodes === null) {
            return 0;
        }

        $traverser = new NodeTraverser;

        $cyclomaticComplexityCalculatingVisitor = new CyclomaticComplexityCalculatingVisitor;

        $traverser->addVisitor($cyclomaticComplexityCalculatingVisitor);

        /* @noinspection UnusedFunctionResultInspection */
        $traverser->traverse($nodes);

        return $cyclomaticComplexityCalculatingVisitor->cyclomaticComplexity();
    }

    /**
     * @psalm-param ClassMethod|Function_ $node
     */
    private function signature(Node $node): string
    {
        assert($node instanceof ClassMethod || $node instanceof Function_);

        $signature  = ($node->returnsByRef() ? '&' : '') . $node->name->toString() . '(';
        $parameters = [];

        foreach ($node->getParams() as $parameter) {
            assert(isset($parameter->var->name));

            $parameterAsString = '';

            if ($parameter->type !== null) {
                $parameterAsString = $this->type($parameter->type) . ' ';
            }

            $parameterAsString .= '$' . $parameter->var->name;

            /* @todo Handle default values */

            $parameters[] = $parameterAsString;
        }

        $signature .= implode(', ', $parameters) . ')';

        $returnType = $node->getReturnType();

        if ($returnType !== null) {
            $signature .= ': ' . $this->type($returnType);
        }

        return $signature;
    }

    /**
     * @psalm-param Identifier|Name|NullableType|UnionType $type
     */
    private function type(Node $type): string
    {
        assert($type instanceof Identifier || $type instanceof Name || $type instanceof NullableType || $type instanceof UnionType);

        if ($type instanceof NullableType) {
            return '?' . $type->type;
        }

        if ($type instanceof UnionType) {
            $types = [];

            foreach ($type->types as $_type) {
                $types[] = $_type->toString();
            }

            return implode('|', $types);
        }

        return $type->toString();
    }

    private function visibility(ClassMethod $node): string
    {
        if ($node->isPrivate()) {
            return 'private';
        }

        if ($node->isProtected()) {
            return 'protected';
        }

        return 'public';
    }

    private function processClass(Class_ $node): void
    {
        $name           = $node->name->toString();
        $namespacedName = $node->namespacedName->toString();

        $this->classes[$namespacedName] = [
            'name'           => $name,
            'namespacedName' => $namespacedName,
            'namespace'      => $this->namespace($namespacedName, $name),
            'startLine'      => $node->getStartLine(),
            'endLine'        => $node->getEndLine(),
            'methods'        => [],
        ];
    }

    private function processTrait(Trait_ $node): void
    {
        $name           = $node->name->toString();
        $namespacedName = $node->namespacedName->toString();

        $this->traits[$namespacedName] = [
            'name'           => $name,
            'namespacedName' => $namespacedName,
            'namespace'      => $this->namespace($namespacedName, $name),
            'startLine'      => $node->getStartLine(),
            'endLine'        => $node->getEndLine(),
            'methods'        => [],
        ];
    }

    private function processMethod(ClassMethod $node): void
    {
        $parentNode = $node->getAttribute('parent');

        if ($parentNode instanceof Interface_) {
            return;
        }

        assert($parentNode instanceof Class_ || $parentNode instanceof Trait_);
        assert(isset($parentNode->name));
        assert(isset($parentNode->namespacedName));
        assert($parentNode->namespacedName instanceof Name);

        $parentName           = $parentNode->name->toString();
        $parentNamespacedName = $parentNode->namespacedName->toString();

        if ($parentNode instanceof Class_) {
            $storage = &$this->classes;
        } else {
            $storage = &$this->traits;
        }

        if (!isset($storage[$parentNamespacedName])) {
            $storage[$parentNamespacedName] = [
                'name'           => $parentName,
                'namespacedName' => $parentNamespacedName,
                'namespace'      => $this->namespace($parentNamespacedName, $parentName),
                'startLine'      => $parentNode->getStartLine(),
                'endLine'        => $parentNode->getEndLine(),
                'methods'        => [],
            ];
        }

        $storage[$parentNamespacedName]['methods'][$node->name->toString()] = [
            'methodName' => $node->name->toString(),
            'signature'  => $this->signature($node),
            'visibility' => $this->visibility($node),
            'startLine'  => $node->getStartLine(),
            'endLine'    => $node->getEndLine(),
            'ccn'        => $this->cyclomaticComplexity($node),
        ];
    }

    private function processFunction(Function_ $node): void
    {
        assert(isset($node->name));
        assert(isset($node->namespacedName));
        assert($node->namespacedName instanceof Name);

        $name           = $node->name->toString();
        $namespacedName = $node->namespacedName->toString();

        $this->functions[$namespacedName] = [
            'name'           => $name,
            'namespacedName' => $namespacedName,
            'namespace'      => $this->namespace($namespacedName, $name),
            'signature'      => $this->signature($node),
            'startLine'      => $node->getStartLine(),
            'endLine'        => $node->getEndLine(),
            'ccn'            => $this->cyclomaticComplexity($node),
        ];
    }

    private function namespace(string $namespacedName, string $name): string
    {
        return trim(rtrim($namespacedName, $name), '\\');
    }
}
