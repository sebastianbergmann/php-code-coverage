<?php declare(strict_types = 1);

namespace PHPStan\Rules\PHPUnit;

use PhpParser\Node;
use PhpParser\Node\Expr\MethodCall;
use PHPStan\Analyser\Scope;
use PHPStan\Rules\IdentifierRuleError;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use PHPStan\Type\Type;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\MockObject\Stub;
use function array_filter;
use function count;
use function implode;
use function in_array;
use function sprintf;

/**
 * @implements Rule<MethodCall>
 */
class MockMethodCallRule implements Rule
{

	public function getNodeType(): string
	{
		return Node\Expr\MethodCall::class;
	}

	public function processNode(Node $node, Scope $scope): array
	{
		if (!$node->name instanceof Node\Identifier || $node->name->name !== 'method') {
			return [];
		}

		if (count($node->getArgs()) < 1) {
			return [];
		}

		$argType = $scope->getType($node->getArgs()[0]->value);
		if (count($argType->getConstantStrings()) === 0) {
			return [];
		}

		$errors = [];
		foreach ($argType->getConstantStrings() as $constantString) {
			$method = $constantString->getValue();
			$type = $scope->getType($node->var);

			$error = $this->checkCallOnType($scope, $type, $method);
			if ($error !== null) {
				$errors[] = $error;
				continue;
			}

			if (!$node->var instanceof MethodCall) {
				continue;
			}

			if (!$node->var->name instanceof Node\Identifier) {
				continue;
			}

			if ($node->var->name->toLowerString() !== 'expects') {
				continue;
			}

			$varType = $scope->getType($node->var->var);
			$error = $this->checkCallOnType($scope, $varType, $method);
			if ($error === null) {
				continue;
			}

			$errors[] = $error;
		}

		return $errors;
	}

	private function checkCallOnType(Scope $scope, Type $type, string $method): ?IdentifierRuleError
	{
		$methodReflection = $scope->getMethodReflection($type, $method);
		if ($methodReflection !== null) {
			return null;
		}

		if (
			in_array(MockObject::class, $type->getObjectClassNames(), true)
			|| in_array(Stub::class, $type->getObjectClassNames(), true)
		) {
			$mockClasses = array_filter($type->getObjectClassNames(), static fn (string $class): bool => $class !== MockObject::class && $class !== Stub::class);
			if (count($mockClasses) === 0) {
				return null;
			}

			return RuleErrorBuilder::message(sprintf(
				'Trying to mock an undefined method %s() on class %s.',
				$method,
				implode('&', $mockClasses),
			))->identifier('phpunit.mockMethod')->build();
		}

		return null;
	}

}
