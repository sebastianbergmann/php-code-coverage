<?php declare(strict_types = 1);

namespace PHPStan\Type\PHPUnit;

use PhpParser\Node;
use PHPStan\Analyser\Error;
use PHPStan\Analyser\IgnoreErrorExtension;
use PHPStan\Analyser\Scope;
use PHPStan\Rules\PHPUnit\DataProviderHelper;
use PHPStan\Rules\PHPUnit\TestMethodsHelper;
use function in_array;

final class DataProviderReturnTypeIgnoreExtension implements IgnoreErrorExtension
{

	private TestMethodsHelper $testMethodsHelper;

	private DataProviderHelper $dataProviderHelper;

	public function __construct(
		TestMethodsHelper $testMethodsHelper,
		DataProviderHelper $dataProviderHelper
	)
	{
		$this->testMethodsHelper = $testMethodsHelper;
		$this->dataProviderHelper = $dataProviderHelper;
	}

	public function shouldIgnore(Error $error, Node $node, Scope $scope): bool
	{
		if (!in_array($error->getIdentifier(), [
			'missingType.iterableValue',
			'missingType.generics',
		], true)) {
			return false;
		}

		if (!$scope->isInClass()) {
			return false;
		}
		$classReflection = $scope->getClassReflection();

		$methodReflection = $scope->getFunction();
		if ($methodReflection === null) {
			return false;
		}

		$testMethods = $this->testMethodsHelper->getTestMethods($classReflection, $scope);
		foreach ($testMethods as $testMethod) {
			foreach ($this->dataProviderHelper->getDataProviderMethods($scope, $testMethod, $classReflection) as [, $providerMethodName]) {
				if ($providerMethodName === $methodReflection->getName()) {
					return true;
				}
			}
		}

		return false;
	}

}
