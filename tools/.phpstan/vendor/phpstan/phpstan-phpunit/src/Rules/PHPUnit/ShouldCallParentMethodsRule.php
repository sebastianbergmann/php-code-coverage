<?php declare(strict_types = 1);

namespace PHPStan\Rules\PHPUnit;

use PhpParser\Node;
use PhpParser\Node\Stmt\ClassMethod;
use PhpParser\NodeTraverser;
use PhpParser\NodeVisitor;
use PhpParser\NodeVisitorAbstract;
use PHPStan\Analyser\Scope;
use PHPStan\Node\InClassMethodNode;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use PHPUnit\Framework\TestCase;
use function array_pop;
use function array_splice;
use function count;
use function end;
use function in_array;
use function sprintf;
use function strtolower;

/**
 * @implements Rule<InClassMethodNode>
 */
class ShouldCallParentMethodsRule implements Rule
{

	public function getNodeType(): string
	{
		return InClassMethodNode::class;
	}

	public function processNode(Node $node, Scope $scope): array
	{
		$methodName = $node->getOriginalNode()->name->name;
		if (!in_array(strtolower($methodName), ['setup', 'teardown'], true)) {
			return [];
		}
		if ($scope->getClassReflection() === null) {
			return [];
		}

		if (!$scope->getClassReflection()->is(TestCase::class)) {
			return [];
		}

		$parentClass = $scope->getClassReflection()->getParentClass();

		if ($parentClass === null) {
			return [];
		}
		if (!$parentClass->hasNativeMethod($methodName)) {
			return [];
		}

		$parentMethod = $parentClass->getNativeMethod($methodName);
		if ($parentMethod->getDeclaringClass()->getName() === TestCase::class) {
			return [];
		}

		$stmts = $node->getOriginalNode()->getStmts() ?? [];
		if ($this->hasParentClassCall($stmts, strtolower($methodName))) {
			return [];
		}

		$errorBuilder = RuleErrorBuilder::message(
			sprintf('Missing call to parent::%s() method.', $methodName),
		)->identifier('phpunit.callParent');

		if ($scope->isInTrait()) {
			return [$errorBuilder->build()];
		}

		$position = $this->getParentCallPosition($stmts, $methodName);
		if ($position === null) {
			return [$errorBuilder->build()];
		}

		$parentMethodName = $parentMethod->getName();
		$errorBuilder->fixNode($node->getOriginalNode(), static function (ClassMethod $methodNode) use ($position, $parentMethodName): ClassMethod {
			$stmts = $methodNode->stmts ?? [];
			array_splice($stmts, $position, 0, [
				new Node\Stmt\Expression(
					new Node\Expr\StaticCall(new Node\Name('parent'), new Node\Identifier($parentMethodName)),
				),
			]);
			$methodNode->stmts = $stmts;

			return $methodNode;
		});

		return [$errorBuilder->build()];
	}

	/**
	 * @param Node\Stmt[] $stmts
	 */
	private function hasParentClassCall(array $stmts, string $methodName): bool
	{
		return $this->findInMethodScope($stmts, static fn (Node $node): bool => $node instanceof Node\Expr\StaticCall
				&& !$node->isFirstClassCallable()
				&& $node->class instanceof Node\Name
				&& $node->class->toLowerString() === 'parent'
				&& $node->name instanceof Node\Identifier
				&& $node->name->toLowerString() === $methodName) !== [];
	}

	/**
	 * setUp() gets the parent call as its first statement, tearDown() as its last one
	 * (before a trailing return). A tearDown() with an early return or with a throw
	 * is not fixable, because the appended call would be skipped on that path.
	 *
	 * @param Node\Stmt[] $stmts
	 */
	private function getParentCallPosition(array $stmts, string $methodName): ?int
	{
		if (strtolower($methodName) === 'setup') {
			return 0;
		}

		$position = count($stmts);
		$exits = $this->findInMethodScope($stmts, static fn (Node $node): bool => $node instanceof Node\Stmt\Return_ || $node instanceof Node\Expr\Throw_);

		if (end($stmts) instanceof Node\Stmt\Return_) {
			// the trailing return is the last one found
			array_pop($exits);
			$position--;
		}

		if ($exits !== []) {
			return null;
		}

		return $position;
	}

	/**
	 * Finds nodes in the method body, skipping nested functions, closures and classes.
	 *
	 * @param Node\Stmt[] $stmts
	 * @param callable(Node): bool $filter
	 * @return list<Node>
	 */
	private function findInMethodScope(array $stmts, callable $filter): array
	{
		$visitor = new class ($filter) extends NodeVisitorAbstract {

			/** @var callable(Node): bool */
			private $filter;

			/** @var list<Node> */
			public array $found = [];

			/**
			 * @param callable(Node): bool $filter
			 */
			public function __construct(callable $filter)
			{
				$this->filter = $filter;
			}

			public function enterNode(Node $node): ?int
			{
				if ($node instanceof Node\FunctionLike || $node instanceof Node\Stmt\ClassLike) {
					return NodeVisitor::DONT_TRAVERSE_CHILDREN;
				}

				if (($this->filter)($node)) {
					$this->found[] = $node;
				}

				return null;
			}

		};

		$traverser = new NodeTraverser($visitor);
		$traverser->traverse($stmts);

		return $visitor->found;
	}

}
