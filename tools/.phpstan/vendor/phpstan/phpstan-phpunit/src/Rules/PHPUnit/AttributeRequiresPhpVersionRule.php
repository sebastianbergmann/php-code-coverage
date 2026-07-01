<?php declare(strict_types = 1);

namespace PHPStan\Rules\PHPUnit;

use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\Node\InClassMethodNode;
use PHPStan\Rules\Rule;
use PHPUnit\Framework\TestCase;
use function array_merge;

/**
 * @implements Rule<InClassMethodNode>
 */
class AttributeRequiresPhpVersionRule implements Rule
{

	private TestMethodsHelper $testMethodsHelper;

	private AttributeVersionRequirementHelper $attributeVersionRequirementHelper;

	public function __construct(
		TestMethodsHelper $testMethodsHelper,
		AttributeVersionRequirementHelper $attributeVersionRequirementHelper
	)
	{
		$this->testMethodsHelper = $testMethodsHelper;
		$this->attributeVersionRequirementHelper = $attributeVersionRequirementHelper;
	}

	public function getNodeType(): string
	{
		return InClassMethodNode::class;
	}

	public function processNode(Node $node, Scope $scope): array
	{
		$classReflection = $scope->getClassReflection();
		if ($classReflection === null || $classReflection->is(TestCase::class) === false) {
			return [];
		}

		$reflectionMethod = $this->testMethodsHelper->getTestMethodReflection($classReflection, $node->getMethodReflection(), $scope);
		if ($reflectionMethod === null) {
			return [];
		}

		return $this->attributeVersionRequirementHelper->checkVersionRequirement(
			array_merge(
				$reflectionMethod->getAttributesByName('PHPUnit\Framework\Attributes\RequiresPhp'),
				$reflectionMethod->getAttributesByName('PHPUnit\Framework\Attributes\RequiresPhpunit'),
			),
			$scope,
		);
	}

}
