<?php declare(strict_types = 1);

namespace PHPStan\Type\PHPUnit;

use PhpParser\Node\Arg;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Expr\StaticCall;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\MethodReflection;
use PHPStan\Type\DynamicMethodReturnTypeExtension;
use PHPStan\Type\DynamicStaticMethodReturnTypeExtension;
use PHPStan\Type\ObjectType;
use PHPStan\Type\Type;
use PHPStan\Type\TypeCombinator;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\MockObject\Stub;
use PHPUnit\Framework\TestCase;
use function count;

class MockForIntersectionDynamicReturnTypeExtension implements DynamicMethodReturnTypeExtension, DynamicStaticMethodReturnTypeExtension
{

	public function getClass(): string
	{
		return TestCase::class;
	}

	public function isMethodSupported(MethodReflection $methodReflection): bool
	{
		return $methodReflection->getName() === 'createMockForIntersectionOfInterfaces';
	}

	public function isStaticMethodSupported(MethodReflection $methodReflection): bool
	{
		return $methodReflection->getName() === 'createStubForIntersectionOfInterfaces';
	}

	public function getTypeFromStaticMethodCall(MethodReflection $methodReflection, StaticCall $methodCall, Scope $scope): ?Type
	{
		return $this->getTypeFromCall($methodReflection, $methodCall->getArgs(), $scope);
	}

	public function getTypeFromMethodCall(MethodReflection $methodReflection, MethodCall $methodCall, Scope $scope): ?Type
	{
		return $this->getTypeFromCall($methodReflection, $methodCall->getArgs(), $scope);
	}

	/**
	 * @param array<Arg> $args
	 */
	private function getTypeFromCall(MethodReflection $methodReflection, array $args, Scope $scope): ?Type
	{
		if (!isset($args[0])) {
			return null;
		}

		$interfaces = $scope->getType($args[0]->value);
		$constantArrays = $interfaces->getConstantArrays();
		if (count($constantArrays) !== 1) {
			return null;
		}

		$constantArray = $constantArrays[0];
		if (count($constantArray->getOptionalKeys()) > 0) {
			return null;
		}

		$result = [];
		if ($methodReflection->getName() === 'createMockForIntersectionOfInterfaces') {
			$result[] = new ObjectType(MockObject::class);
		} else {
			$result[] = new ObjectType(Stub::class);
		}

		foreach ($constantArray->getValueTypes() as $valueType) {
			if (!$valueType->isClassString()->yes()) {
				return null;
			}

			$values = $valueType->getConstantScalarValues();
			if (count($values) !== 1) {
				return null;
			}

			$result[] = new ObjectType((string) $values[0]);
		}

		return TypeCombinator::intersect(...$result);
	}

}
