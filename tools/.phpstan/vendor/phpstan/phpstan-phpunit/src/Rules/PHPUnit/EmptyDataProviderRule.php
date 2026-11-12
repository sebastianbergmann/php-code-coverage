<?php declare(strict_types = 1);

namespace PHPStan\Rules\PHPUnit;

use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\Node\MethodReturnStatementsNode;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use PHPUnit\Framework\TestCase;
use function sprintf;
use function strcasecmp;

/**
 * @implements Rule<MethodReturnStatementsNode>
 */
class EmptyDataProviderRule implements Rule
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
		return MethodReturnStatementsNode::class;
	}

	public function processNode(Node $node, Scope $scope): array
	{
		// A generator yields its data sets one at a time, so whether it provides
		// any depends on control flow this rule cannot decide.
		if ($node->isGenerator()) {
			return [];
		}

		$returnStatements = $node->getReturnStatements();
		if ($returnStatements === []) {
			return [];
		}

		if ($this->PHPUnitVersion->rejectsEmptyDataProviders()->no()) {
			return [];
		}

		if (!$node->getClassReflection()->is(TestCase::class)) {
			return [];
		}

		foreach ($returnStatements as $returnStatement) {
			$returnExpr = $returnStatement->getReturnNode()->expr;
			if ($returnExpr === null) {
				return [];
			}

			$returnType = $returnStatement->getScope()->getType($returnExpr);
			if (!$returnType->isIterable()->yes() || !$returnType->isIterableAtLeastOnce()->no()) {
				return [];
			}
		}

		if (!$this->isDataProvider($node, $scope)) {
			return [];
		}

		return [
			RuleErrorBuilder::message(sprintf(
				'Data provider method %s() provides no data sets, which is an error in PHPUnit 10 and newer.',
				$node->getMethodName(),
			))
				->identifier('phpunit.dataProviderEmpty')
				->build(),
		];
	}

	private function isDataProvider(MethodReturnStatementsNode $node, Scope $scope): bool
	{
		$classReflection = $node->getClassReflection();

		foreach ($this->testMethodsHelper->getTestMethods($classReflection, $scope) as $testMethod) {
			foreach ($this->dataProviderHelper->getDataProviderMethods($scope, $testMethod, $classReflection) as [$providerClassReflection, $providerMethodName]) {
				// A provider declared in another class is checked when that class is analysed.
				if ($providerClassReflection === null || $providerClassReflection->getName() !== $classReflection->getName()) {
					continue;
				}

				if (strcasecmp($providerMethodName, $node->getMethodName()) === 0) {
					return true;
				}
			}
		}

		return false;
	}

}
