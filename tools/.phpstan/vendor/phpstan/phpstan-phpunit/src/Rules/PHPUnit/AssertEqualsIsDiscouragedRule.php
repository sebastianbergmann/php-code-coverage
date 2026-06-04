<?php declare(strict_types = 1);

namespace PHPStan\Rules\PHPUnit;

use PhpParser\Node;
use PhpParser\Node\Expr\CallLike;
use PHPStan\Analyser\Scope;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use PHPStan\Type\GeneralizePrecision;
use PHPStan\Type\TypeCombinator;
use function count;
use function in_array;
use function sprintf;
use function strtolower;

/**
 * @implements Rule<CallLike>
 */
class AssertEqualsIsDiscouragedRule implements Rule
{

	public function getNodeType(): string
	{
		return CallLike::class;
	}

	public function processNode(Node $node, Scope $scope): array
	{
		if (!$node instanceof Node\Expr\MethodCall && ! $node instanceof Node\Expr\StaticCall) {
			return [];
		}
		if (count($node->getArgs()) < 2) {
			return [];
		}
		if ($node->isFirstClassCallable()) {
			return [];
		}

		if (
			!$node->name instanceof Node\Identifier
			|| !in_array(strtolower($node->name->name), ['assertequals', 'assertnotequals'], true)
		) {
			return [];
		}

		if (!AssertRuleHelper::isMethodOrStaticCallOnAssert($node, $scope)) {
			return [];
		}

		$leftType = TypeCombinator::removeNull($scope->getType($node->getArgs()[0]->value));
		$rightType = TypeCombinator::removeNull($scope->getType($node->getArgs()[1]->value));

		if ($leftType->isConstantScalarValue()->yes()) {
			$leftType = $leftType->generalize(GeneralizePrecision::lessSpecific());
		}
		if ($rightType->isConstantScalarValue()->yes()) {
			$rightType = $rightType->generalize(GeneralizePrecision::lessSpecific());
		}

		if (
			($leftType->isScalar()->yes() && $rightType->isScalar()->yes())
			&& ($leftType->isSuperTypeOf($rightType)->yes())
			&& ($rightType->isSuperTypeOf($leftType)->yes())
		) {
			$correctName = strtolower($node->name->name) === 'assertnotequals' ? 'assertNotSame' : 'assertSame';
			return [
				RuleErrorBuilder::message(
					sprintf(
						'You should use %s() instead of %s(), because both values are scalars of the same type',
						$correctName,
						$node->name->name,
					),
				)->identifier('phpunit.assertEquals')
					->fixNode($node, static function (CallLike $node) use ($correctName) {
						$node->name = new Node\Identifier($correctName);

						return $node;
					})
					->build(),
			];
		}

		return [];
	}

}
