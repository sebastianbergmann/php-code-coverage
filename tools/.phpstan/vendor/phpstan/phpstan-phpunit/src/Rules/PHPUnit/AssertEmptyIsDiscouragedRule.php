<?php declare(strict_types = 1);

namespace PHPStan\Rules\PHPUnit;

use Countable;
use PhpParser\Node;
use PhpParser\Node\Expr\CallLike;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Expr\StaticCall;
use PhpParser\Node\Identifier;
use PhpParser\Node\Scalar\Int_;
use PhpParser\Node\Scalar\String_;
use PHPStan\Analyser\Scope;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use PHPStan\Type\Type;
use PHPStan\Type\TypeCombinator;
use PHPStan\Type\UnionType;
use function array_merge;
use function count;
use function in_array;
use function sprintf;

/**
 * @implements Rule<CallLike>
 */
class AssertEmptyIsDiscouragedRule implements Rule
{

	public function getNodeType(): string
	{
		return CallLike::class;
	}

	public function processNode(Node $node, Scope $scope): array
	{
		if (!($node instanceof MethodCall) && !($node instanceof StaticCall)) {
			return [];
		}

		if ($node->isFirstClassCallable() || count($node->getArgs()) < 1) {
			return [];
		}

		if (!$node->name instanceof Identifier || !in_array($node->name->toLowerString(), ['assertempty', 'assertnotempty'], true)) {
			return [];
		}

		if (!AssertRuleHelper::isMethodOrStaticCallOnAssert($node, $scope)) {
			return [];
		}

		$errorBuilder = RuleErrorBuilder::message(sprintf('%s() is not allowed. Use more strict assertion.', $node->name->toString()))
			->identifier('phpunit.assertEmpty');

		if (AssertRuleHelper::hasNamedOrUnpackedArguments($node)) {
			return [$errorBuilder->build()];
		}

		$replacement = $this->getReplacement($scope->getNativeType($node->getArgs()[0]->value), $node->name->toLowerString() === 'assertnotempty');
		if ($replacement !== null) {
			[$correctName, $expectedValue] = $replacement;
			$errorBuilder->fixNode($node, static function (CallLike $node) use ($correctName, $expectedValue) {
				$node->name = new Identifier($correctName);
				if ($expectedValue !== null) {
					$node->args = array_merge([new Node\Arg($expectedValue)], $node->args);
				}

				return $node;
			});
		}

		return [
			$errorBuilder->build(),
		];
	}

	/**
	 * @return array{string, Node\Expr|null}|null
	 */
	private function getReplacement(Type $type, bool $negated): ?array
	{
		if ($type instanceof UnionType) {
			if (TypeCombinator::containsNull($type)) {
				$typeWithoutNull = TypeCombinator::removeNull($type);

				$classReflections = $typeWithoutNull->getObjectClassReflections();
				if (count($classReflections) === 0) {
					return null;
				}
				foreach ($classReflections as $classReflection) {
					if (
						$classReflection->isBuiltin()
						|| !$classReflection->isFinalByKeyword()
						|| $classReflection->implementsInterface(Countable::class)
					) {
						return null;
					}

					$parentClass = $classReflection->getParentClass();
					while ($parentClass !== null) {
						// Builtin parents can define different empty() semantics.
						if ($parentClass->isBuiltin()) {
							return null;
						}
						$parentClass = $parentClass->getParentClass();
					}
				}

				return [$negated ? 'assertNotNull' : 'assertNull', null];
			}

			return null;
		}

		if ($type->isBoolean()->yes()) {
			return [$negated ? 'assertTrue' : 'assertFalse', null];
		}
		if ($type->isArray()->yes()) {
			return [$negated ? 'assertNotCount' : 'assertCount', new Int_(0)];
		}
		if ($type->isInteger()->yes()) {
			return [$negated ? 'assertNotSame' : 'assertSame', new Int_(0)];
		}
		if ($type->isNonFalsyString()->yes()) {
			return [$negated ? 'assertNotSame' : 'assertSame', new String_('')];
		}

		return null;
	}

}
