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

use function assert;
use function implode;
use function is_string;
use function max;
use function strlen;
use function substr;
use PhpParser\Node;
use PhpParser\Node\ComplexType;
use PhpParser\Node\Expr\Variable;
use PhpParser\Node\Identifier;
use PhpParser\Node\IntersectionType;
use PhpParser\Node\Name;
use PhpParser\Node\NullableType;
use PhpParser\Node\Stmt\Class_ as PhpParserClass_;
use PhpParser\Node\Stmt\ClassMethod;
use PhpParser\Node\Stmt\Enum_ as PhpParserEnum;
use PhpParser\Node\Stmt\Function_ as PhpParserFunction_;
use PhpParser\Node\Stmt\Interface_ as PhpParserInterface_;
use PhpParser\Node\Stmt\Trait_ as PhpParserTrait_;
use PhpParser\Node\UnionType;
use PhpParser\NodeTraverser;
use PhpParser\NodeVisitorAbstract;
use SebastianBergmann\Complexity\CyclomaticComplexityCalculatingVisitor;

/**
 * @internal This class is not covered by the backward compatibility promise for phpunit/php-code-coverage
 *
 * @no-named-arguments Parameter names are not covered by the backward compatibility promise for phpunit/php-code-coverage
 */
final class CodeUnitFindingVisitor extends NodeVisitorAbstract
{
    /**
     * @var non-empty-string
     */
    private string $file;

    /**
     * @var array<string, Interface_>
     */
    private array $interfaces = [];

    /**
     * @var array<string, Class_>
     */
    private array $classes = [];

    /**
     * @var array<string, Trait_>
     */
    private array $traits = [];

    /**
     * @var array<string, Function_>
     */
    private array $functions = [];

    /**
     * @param non-empty-string $file
     */
    public function __construct(string $file)
    {
        $this->file = $file;
    }

    public function enterNode(Node $node): null
    {
        if ($node instanceof PhpParserInterface_) {
            $this->processInterface($node);

            return null;
        }

        if ($node instanceof PhpParserClass_ && !$node->isAnonymous()) {
            $this->processClass($node);

            return null;
        }

        if ($node instanceof PhpParserEnum) {
            $this->processClass($node);

            return null;
        }

        if ($node instanceof PhpParserTrait_) {
            $this->processTrait($node);

            return null;
        }

        if ($node instanceof PhpParserFunction_) {
            $this->processFunction($node);
        }

        return null;
    }

    public function leaveNode(Node $node): null
    {
        if ($node instanceof PhpParserClass_ && $node->isAnonymous()) {
            return null;
        }

        if (!$node instanceof PhpParserClass_ && !$node instanceof PhpParserEnum && !$node instanceof PhpParserTrait_) {
            return null;
        }

        $traits = $this->traitUses($node);

        if ($traits === []) {
            return null;
        }

        $this->setTraits($node, $traits);

        return null;
    }

    /**
     * @return array<string, Interface_>
     */
    public function interfaces(): array
    {
        return $this->interfaces;
    }

    /**
     * @return array<string, Class_>
     */
    public function classes(): array
    {
        return $this->classes;
    }

    /**
     * @return array<string, Trait_>
     */
    public function traits(): array
    {
        return $this->traits;
    }

    /**
     * @return array<string, Function_>
     */
    public function functions(): array
    {
        return $this->functions;
    }

    /**
     * @return non-negative-int
     */
    private function cyclomaticComplexity(ClassMethod|PhpParserFunction_ $node): int
    {
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
     * @return non-empty-string
     */
    private function signature(ClassMethod|PhpParserFunction_ $node): string
    {
        $signature  = ($node->returnsByRef() ? '&' : '') . $node->name->toString() . '(';
        $parameters = [];

        foreach ($node->getParams() as $parameter) {
            $variable = $parameter->var;

            if (!$variable instanceof Variable || !is_string($variable->name)) {
                continue; // @codeCoverageIgnore
            }

            $parameterAsString = '';

            if ($parameter->type !== null) {
                $parameterAsString = $this->type($parameter->type) . ' ';
            }

            $parameterAsString .= '$' . $variable->name;

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

    private function type(ComplexType|Identifier|Name $type): string
    {
        if ($type instanceof NullableType) {
            return '?' . $type->type;
        }

        if ($type instanceof UnionType) {
            return $this->unionTypeAsString($type);
        }

        if ($type instanceof IntersectionType) {
            return $this->intersectionTypeAsString($type);
        }

        if ($type instanceof Identifier || $type instanceof Name) {
            return $type->toString();
        }

        // @codeCoverageIgnoreStart
        return '';
        // @codeCoverageIgnoreEnd
    }

    private function visibility(ClassMethod $node): Visibility
    {
        if ($node->isPrivate()) {
            return Visibility::Private;
        }

        if ($node->isProtected()) {
            return Visibility::Protected;
        }

        return Visibility::Public;
    }

    private function processInterface(PhpParserInterface_ $node): void
    {
        assert(isset($node->name));
        assert(isset($node->namespacedName));
        assert($node->namespacedName instanceof Name);

        $name             = $node->name->toString();
        $namespacedName   = $node->namespacedName->toString();
        $parentInterfaces = [];

        foreach ($node->extends as $parentInterface) {
            $parentInterfaces[] = $parentInterface->toString();
        }

        $this->interfaces[$namespacedName] = new Interface_(
            $name,
            $namespacedName,
            $this->namespace($namespacedName, $name),
            $this->startLine($node),
            $this->endLine($node),
            $parentInterfaces,
        );
    }

    private function processClass(PhpParserClass_|PhpParserEnum $node): void
    {
        assert(isset($node->name));
        assert(isset($node->namespacedName));
        assert($node->namespacedName instanceof Name);

        $name           = $node->name->toString();
        $namespacedName = $node->namespacedName->toString();
        $parentClass    = null;
        $interfaces     = [];

        if (!$node instanceof PhpParserEnum) {
            if ($node->extends instanceof Name) {
                $parentClass = $node->extends->toString();
            }

            foreach ($node->implements as $interface) {
                $interfaces[] = $interface->toString();
            }
        }

        $this->classes[$namespacedName] = new Class_(
            $name,
            $namespacedName,
            $this->namespace($namespacedName, $name),
            $this->file,
            $this->startLine($node),
            $this->endLine($node),
            $parentClass,
            $interfaces,
            [],
            $this->processMethods($node->getMethods()),
        );
    }

    private function processTrait(PhpParserTrait_ $node): void
    {
        assert(isset($node->name));
        assert(isset($node->namespacedName));
        assert($node->namespacedName instanceof Name);

        $name           = $node->name->toString();
        $namespacedName = $node->namespacedName->toString();

        $this->traits[$namespacedName] = new Trait_(
            $name,
            $namespacedName,
            $this->namespace($namespacedName, $name),
            $this->file,
            $this->startLine($node),
            $this->endLine($node),
            [],
            $this->processMethods($node->getMethods()),
        );
    }

    /**
     * @return list<non-empty-string>
     */
    private function traitUses(PhpParserClass_|PhpParserEnum|PhpParserTrait_ $node): array
    {
        $traits = [];

        foreach ($node->getTraitUses() as $traitUse) {
            foreach ($traitUse->traits as $trait) {
                $traits[] = $trait->toString();
            }
        }

        return $traits;
    }

    /**
     * @param list<ClassMethod> $nodes
     *
     * @return array<non-empty-string, Method>
     */
    private function processMethods(array $nodes): array
    {
        $methods = [];

        foreach ($nodes as $node) {
            $methods[$node->name->toString()] = new Method(
                $node->name->toString(),
                $this->startLine($node),
                $this->endLine($node),
                $this->signature($node),
                $this->visibility($node),
                $this->cyclomaticComplexity($node),
            );
        }

        return $methods;
    }

    private function processFunction(PhpParserFunction_ $node): void
    {
        assert(isset($node->name));
        assert(isset($node->namespacedName));
        assert($node->namespacedName instanceof Name);

        $name           = $node->name->toString();
        $namespacedName = $node->namespacedName->toString();

        $this->functions[$namespacedName] = new Function_(
            $name,
            $namespacedName,
            $this->namespace($namespacedName, $name),
            $this->startLine($node),
            $this->endLine($node),
            $this->signature($node),
            $this->cyclomaticComplexity($node),
        );
    }

    /**
     * @return positive-int
     */
    private function startLine(ClassMethod|PhpParserClass_|PhpParserEnum|PhpParserFunction_|PhpParserInterface_|PhpParserTrait_ $node): int
    {
        assert(isset($node->name));

        return max(1, $node->name->getStartLine());
    }

    /**
     * @return positive-int
     */
    private function endLine(ClassMethod|PhpParserClass_|PhpParserEnum|PhpParserFunction_|PhpParserInterface_|PhpParserTrait_ $node): int
    {
        return max(1, $node->getEndLine());
    }

    private function namespace(string $namespacedName, string $name): string
    {
        if ($namespacedName === $name) {
            return '';
        }

        return substr($namespacedName, 0, -strlen($name) - 1);
    }

    private function unionTypeAsString(UnionType $node): string
    {
        $types = [];

        foreach ($node->types as $type) {
            if ($type instanceof IntersectionType) {
                $types[] = '(' . $this->intersectionTypeAsString($type) . ')';

                continue;
            }

            $types[] = $this->typeAsString($type);
        }

        return implode('|', $types);
    }

    private function intersectionTypeAsString(IntersectionType $node): string
    {
        $types = [];

        foreach ($node->types as $type) {
            $types[] = $this->typeAsString($type);
        }

        return implode('&', $types);
    }

    private function typeAsString(Identifier|Name $node): string
    {
        if ($node instanceof Name) {
            return $node->toCodeString();
        }

        return $node->toString();
    }

    /**
     * @param list<non-empty-string> $traits
     */
    private function setTraits(PhpParserClass_|PhpParserEnum|PhpParserTrait_ $node, array $traits): void
    {
        assert(isset($node->namespacedName));
        assert($node->namespacedName instanceof Name);

        $name = $node->namespacedName->toString();

        if ($node instanceof PhpParserClass_ || $node instanceof PhpParserEnum) {
            assert(isset($this->classes[$name]));

            $this->classes[$name] = new Class_(
                $this->classes[$name]->name(),
                $this->classes[$name]->namespacedName(),
                $this->classes[$name]->namespace(),
                $this->classes[$name]->file(),
                $this->classes[$name]->startLine(),
                $this->classes[$name]->endLine(),
                $this->classes[$name]->parentClass(),
                $this->classes[$name]->interfaces(),
                $traits,
                $this->classes[$name]->methods(),
            );

            return;
        }

        assert(isset($this->traits[$name]));

        $this->traits[$name] = new Trait_(
            $this->traits[$name]->name(),
            $this->traits[$name]->namespacedName(),
            $this->traits[$name]->namespace(),
            $this->traits[$name]->file(),
            $this->traits[$name]->startLine(),
            $this->traits[$name]->endLine(),
            $traits,
            $this->traits[$name]->methods(),
        );
    }
}
