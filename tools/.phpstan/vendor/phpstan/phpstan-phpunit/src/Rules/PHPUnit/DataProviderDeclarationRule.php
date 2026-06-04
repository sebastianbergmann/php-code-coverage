<?php declare(strict_types = 1);

namespace PHPStan\Rules\PHPUnit;

use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\Rules\Rule;
use PHPUnit\Framework\TestCase;
use function array_merge;

/**
 * @implements Rule<Node\Stmt\ClassMethod>
 */
class DataProviderDeclarationRule implements Rule
{

	/**
	 * Data provider helper.
	 *
	 */
	private DataProviderHelper $dataProviderHelper;

	/**
	 * When set to true, it reports data provider method with incorrect name case.
	 *
	 */
	private bool $checkFunctionNameCase;

	/**
	 * When phpstan-deprecation-rules is installed, it reports deprecated usages.
	 *
	 */
	private bool $deprecationRulesInstalled;

	public function __construct(
		DataProviderHelper $dataProviderHelper,
		bool $checkFunctionNameCase,
		bool $deprecationRulesInstalled
	)
	{
		$this->dataProviderHelper = $dataProviderHelper;
		$this->checkFunctionNameCase = $checkFunctionNameCase;
		$this->deprecationRulesInstalled = $deprecationRulesInstalled;
	}

	public function getNodeType(): string
	{
		return Node\Stmt\ClassMethod::class;
	}

	public function processNode(Node $node, Scope $scope): array
	{
		$classReflection = $scope->getClassReflection();

		if ($classReflection === null || !$classReflection->is(TestCase::class)) {
			return [];
		}

		$errors = [];

		foreach ($this->dataProviderHelper->getDataProviderMethods($scope, $node, $classReflection) as $dataProviderValue => [$dataProviderClassReflection, $dataProviderMethodName, $lineNumber]) {
			$errors = array_merge(
				$errors,
				$this->dataProviderHelper->processDataProvider(
					$dataProviderValue,
					$dataProviderClassReflection,
					$dataProviderMethodName,
					$lineNumber,
					$this->checkFunctionNameCase,
					$this->deprecationRulesInstalled,
				),
			);
		}

		return $errors;
	}

}
