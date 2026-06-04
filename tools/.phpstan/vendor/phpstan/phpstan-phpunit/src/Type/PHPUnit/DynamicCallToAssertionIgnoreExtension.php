<?php declare(strict_types = 1);

namespace PHPStan\Type\PHPUnit;

use PhpParser\Node;
use PHPStan\Analyser\Error;
use PHPStan\Analyser\IgnoreErrorExtension;
use PHPStan\Analyser\Scope;
use PHPUnit\Framework\TestCase;
use function is_string;
use function str_starts_with;

final class DynamicCallToAssertionIgnoreExtension implements IgnoreErrorExtension
{

	public function shouldIgnore(Error $error, Node $node, Scope $scope): bool
	{
		if (!$node instanceof Node\Expr\MethodCall) {
			return false;
		}

		if (!$node->var instanceof Node\Expr\Variable) {
			return false;
		}

		if (!is_string($node->var->name) || $node->var->name !== 'this') {
			return false;
		}

		if ($error->getIdentifier() !== 'staticMethod.dynamicCall') {
			return false;
		}

		if (
			!$node->name instanceof Node\Identifier
			|| !str_starts_with($node->name->name, 'assert')
		) {
			return false;
		}

		if (!$scope->isInClass()) {
			return false;
		}

		$classReflection = $scope->getClassReflection();
		return $classReflection->is(TestCase::class);
	}

}
