<?php declare(strict_types = 1);

namespace PHPStan\Rules\PHPUnit;

use PhpParser\Node;
use PhpParser\Node\Expr\CallLike;
use PhpParser\Node\Expr\ConstFetch;
use PhpParser\NodeAbstract;
use PHPStan\Analyser\Scope;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use function count;

/**
 * @implements Rule<CallLike>
 */
class AssertSameNullExpectedRule implements Rule
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
		if (!$node->name instanceof Node\Identifier || $node->name->toLowerString() !== 'assertsame') {
			return [];
		}

		if (!AssertRuleHelper::isMethodOrStaticCallOnAssert($node, $scope)) {
			return [];
		}

		$expectedArgumentValue = $node->getArgs()[0]->value;
		if (!($expectedArgumentValue instanceof ConstFetch)) {
			return [];
		}

		if ($expectedArgumentValue->name->toLowerString() === 'null') {
			return [
				RuleErrorBuilder::message('You should use assertNull() instead of assertSame(null, $actual).')
					->identifier('phpunit.assertNull')
					->fixNode($node, static function (CallLike $node) {
						$node->name = new Node\Identifier('assertNull');
						$node->args = self::rewriteArgs($node->args);

						return $node;
					})
					->build(),
			];
		}

		return [];
	}

	/**
	 * @template T of NodeAbstract
	 * @param array<T> $args
	 * @return list<T>
	 */
	private static function rewriteArgs(array $args): array
	{
		$newArgs = [];
		for ($i = 1; $i < count($args); $i++) {
			$newArgs[] = $args[$i];
		}
		return $newArgs;
	}

}
