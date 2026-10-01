<?php declare(strict_types = 1);

namespace PHPStan\Rules\PHPUnit;

use PhpParser\Node;
use PhpParser\Node\Expr\CallLike;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Expr\StaticCall;
use PhpParser\Node\Identifier;
use PHPStan\Analyser\Scope;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
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

		return [
			RuleErrorBuilder::message(sprintf('%s() is not allowed. Use more strict assertion.', $node->name->toString()))
				->identifier('phpunit.assertEmpty')
				->build(),
		];
	}

}
