<?php declare(strict_types = 1);

namespace PHPStan\Rules\PHPUnit;

use Countable;
use PhpParser\Node;
use PhpParser\Node\Expr\CallLike;
use PHPStan\Analyser\Scope;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use PHPStan\TrinaryLogic;
use PHPStan\Type\Constant\ConstantIntegerType;
use PHPStan\Type\ObjectType;
use PHPStan\Type\Type;
use function count;
use const COUNT_NORMAL;

/**
 * @implements Rule<CallLike>
 */
class AssertSameWithCountRule implements Rule
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
		if (!$node->name instanceof Node\Identifier	|| $node->name->toLowerString() !== 'assertsame') {
			return [];
		}

		if (!AssertRuleHelper::isMethodOrStaticCallOnAssert($node, $scope)) {
			return [];
		}

		$right = $node->getArgs()[1]->value;
		if (self::isCountFunctionCall($right, $scope)) {
			return [
				RuleErrorBuilder::message('You should use assertCount($expectedCount, $variable) instead of assertSame($expectedCount, count($variable)).')
					->identifier('phpunit.assertCount')
					->build(),
			];
		}

		if (self::isCountableMethodCall($right, $scope)) {
			return [
				RuleErrorBuilder::message('You should use assertCount($expectedCount, $variable) instead of assertSame($expectedCount, $variable->count()).')
					->identifier('phpunit.assertCount')
					->build(),
			];
		}

		return [];
	}

	/**
	 * @phpstan-assert-if-true Node\Expr\FuncCall $expr
	 */
	private static function isCountFunctionCall(Node\Expr $expr, Scope $scope): bool
	{
		return $expr instanceof Node\Expr\FuncCall
			&& $expr->name instanceof Node\Name
			&& $expr->name->toLowerString() === 'count'
			&& count($expr->getArgs()) >= 1
			&& self::isNormalCount($expr, $scope->getType($expr->getArgs()[0]->value), $scope)->yes();
	}

	/**
	 * @phpstan-assert-if-true Node\Expr\MethodCall $expr
	 */
	private static function isCountableMethodCall(Node\Expr $expr, Scope $scope): bool
	{
		if (
			$expr instanceof Node\Expr\MethodCall
			&& $expr->name instanceof Node\Identifier
			&& $expr->name->toLowerString() === 'count'
			&& count($expr->getArgs()) === 0
		) {
			$type = $scope->getType($expr->var);

			if ((new ObjectType(Countable::class))->isSuperTypeOf($type)->yes()) {
				return true;
			}
		}

		return false;
	}

	private static function isNormalCount(Node\Expr\FuncCall $countFuncCall, Type $countedType, Scope $scope): TrinaryLogic
	{
		if (count($countFuncCall->getArgs()) === 1) {
			$isNormalCount = TrinaryLogic::createYes();
		} else {
			$mode = $scope->getType($countFuncCall->getArgs()[1]->value);
			$isNormalCount = (new ConstantIntegerType(COUNT_NORMAL))->isSuperTypeOf($mode)->result->or($countedType->getIterableValueType()->isArray()->negate());
		}
		return $isNormalCount;
	}

}
