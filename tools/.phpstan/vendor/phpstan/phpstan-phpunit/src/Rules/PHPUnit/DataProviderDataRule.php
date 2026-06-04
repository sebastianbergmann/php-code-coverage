<?php declare(strict_types = 1);

namespace PHPStan\Rules\PHPUnit;

use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\Node\Expr\TypeExpr;
use PHPStan\Rules\Rule;
use PHPStan\Type\ObjectType;
use PHPStan\Type\Type;
use function array_slice;
use function count;
use function max;
use const PHP_INT_MAX;

/**
 * @implements Rule<Node>
 */
class DataProviderDataRule implements Rule
{

	private TestMethodsHelper $testMethodsHelper;

	private DataProviderHelper $dataProviderHelper;

	private PHPUnitVersion $PHPUnitVersion;

	public function __construct(
		TestMethodsHelper $testMethodsHelper,
		DataProviderHelper $dataProviderHelper,
		PHPUnitVersion $PHPUnitVersion
	)
	{
		$this->testMethodsHelper = $testMethodsHelper;
		$this->dataProviderHelper = $dataProviderHelper;
		$this->PHPUnitVersion = $PHPUnitVersion;
	}

	public function getNodeType(): string
	{
		return Node::class;
	}

	public function processNode(Node $node, Scope $scope): array
	{
		if (
			!$node instanceof Node\Stmt\Return_
			&& !$node instanceof Node\Expr\Yield_
			&& !$node instanceof Node\Expr\YieldFrom
		) {
			return [];
		}

		if ($scope->getFunction() === null) {
			return [];
		}
		if ($scope->isInAnonymousFunction()) {
			return [];
		}

		$classReflection = $scope->getClassReflection();
		if ($classReflection === null) {
			return [];
		}

		$testsWithProvider = [];
		$method = $scope->getFunction();
		$testMethods = $this->testMethodsHelper->getTestMethods($classReflection, $scope);
		foreach ($testMethods as $testMethod) {
			foreach ($this->dataProviderHelper->getDataProviderMethods($scope, $testMethod, $classReflection) as [, $providerMethodName]) {
				if ($providerMethodName === $method->getName()) {
					$testsWithProvider[] = $testMethod;
					continue 2;
				}
			}
		}

		if (count($testsWithProvider) === 0) {
			return [];
		}

		$arraysTypes = $this->buildArrayTypesFromNode($node, $scope);
		if ($arraysTypes === []) {
			return [];
		}

		$maxNumberOfParameters = null;
		foreach ($testsWithProvider as $testMethod) {
			$num = $testMethod->getNumberOfParameters();
			if ($testMethod->isVariadic()) {
				$num = PHP_INT_MAX;
			}
			if ($maxNumberOfParameters === null) {
				$maxNumberOfParameters = $num;
				continue;
			}

			$maxNumberOfParameters = max($maxNumberOfParameters, $num);
			if ($num === PHP_INT_MAX) {
				break;
			}
		}

		foreach ($testsWithProvider as $testMethod) {
			$numberOfParameters = $testMethod->getNumberOfParameters();

			foreach ($arraysTypes as [$startLine, $arraysType]) {
				$args = $this->arrayItemsToArgs($arraysType, $numberOfParameters);
				if ($args === null) {
					continue;
				}

				if (
					!$testMethod->isVariadic()
					&& $numberOfParameters !== $maxNumberOfParameters
				) {
					$args = array_slice($args, 0, $numberOfParameters);
				}

				$scope->invokeNodeCallback(new Node\Expr\MethodCall(
					new TypeExpr(new ObjectType($classReflection->getName())),
					$testMethod->getName(),
					$args,
					['startLine' => $startLine],
				));
			}
		}

		return [];
	}

	/**
	 * @return array<Node\Arg>
	 */
	private function arrayItemsToArgs(Type $array, int $numberOfParameters): ?array
	{
		$args = [];

		$constArrays = $array->getConstantArrays();
		if ($constArrays !== [] && count($constArrays) === 1) {
			$keyTypes = $constArrays[0]->getKeyTypes();
			$valueTypes = $constArrays[0]->getValueTypes();
		} elseif ($array->isArray()->yes()) {
			$keyTypes = [];
			$valueTypes = [];
			for ($i = 0; $i < $numberOfParameters; ++$i) {
				$keyTypes[$i] = $array->getIterableKeyType();
				$valueTypes[$i] = $array->getIterableValueType();
			}
		} else {
			return null;
		}

		foreach ($valueTypes as $i => $valueType) {
			$key = $keyTypes[$i]->getConstantStrings();
			if (count($key) > 1) {
				return null;
			}

			if (count($key) === 0 || !$this->PHPUnitVersion->supportsNamedArgumentsInDataProvider()->yes()) {
				$arg = new Node\Arg(new TypeExpr($valueType));
				$args[] = $arg;
				continue;
			}

			$arg = new Node\Arg(
				new TypeExpr($valueType),
				false,
				false,
				[],
				new Node\Identifier($key[0]->getValue()),
			);
			$args[] = $arg;
		}

		return $args;
	}

	/**
	 * @param Node\Stmt\Return_|Node\Expr\Yield_|Node\Expr\YieldFrom $node
	 *
	 * @return list<list{int, Type}>
	 */
	private function buildArrayTypesFromNode(Node $node, Scope $scope): array
	{
		$arraysTypes = [];

		// special case for providers only containing static data, so we get more precise error lines
		if (
			($node instanceof Node\Stmt\Return_ && $node->expr instanceof Node\Expr\Array_)
			|| ($node instanceof Node\Expr\YieldFrom && $node->expr instanceof Node\Expr\Array_)
		) {
			foreach ($node->expr->items as $item) {
				if (!$item->value instanceof Node\Expr\Array_) {
					$arraysTypes = [];
					break;
				}

				$constArrays = $scope->getType($item->value)->getConstantArrays();
				if ($constArrays === []) {
					$arraysTypes = [];
					break;
				}

				foreach ($constArrays as $constArray) {
					$arraysTypes[] = [$item->value->getStartLine(), $constArray];
				}
			}

			if ($arraysTypes !== []) {
				return $arraysTypes;
			}
		}

		// general case with less precise error message lines
		if ($node instanceof Node\Stmt\Return_ || $node instanceof Node\Expr\YieldFrom) {
			if ($node->expr === null) {
				return [];
			}

			$exprType = $scope->getType($node->expr);
			$exprConstArrays = $exprType->getConstantArrays();
			foreach ($exprConstArrays as $constArray) {
				foreach ($constArray->getValueTypes() as $valueType) {
					foreach ($valueType->getConstantArrays() as $constValueArray) {
						$arraysTypes[] = [$node->getStartLine(), $constValueArray];
					}
				}
			}

			if ($arraysTypes === []) {
				foreach ($exprType->getIterableValueType()->getArrays() as $arrayType) {
					$arraysTypes[] = [$node->getStartLine(), $arrayType];
				}
			}
		} elseif ($node instanceof Node\Expr\Yield_) {
			if ($node->value === null) {
				return [];
			}

			$exprType = $scope->getType($node->value);
			foreach ($exprType->getConstantArrays() as $constValueArray) {
				$arraysTypes[] = [$node->getStartLine(), $constValueArray];
			}
		}

		return $arraysTypes;
	}

}
