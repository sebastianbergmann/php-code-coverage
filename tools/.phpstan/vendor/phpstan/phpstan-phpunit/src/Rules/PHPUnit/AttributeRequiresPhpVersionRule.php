<?php declare(strict_types = 1);

namespace PHPStan\Rules\PHPUnit;

use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\Node\InClassMethodNode;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use PHPUnit\Framework\TestCase;
use function count;
use function is_numeric;
use function sprintf;

/**
 * @implements Rule<InClassMethodNode>
 */
class AttributeRequiresPhpVersionRule implements Rule
{

	private PHPUnitVersion $PHPUnitVersion;

	private TestMethodsHelper $testMethodsHelper;

	/**
	 * When phpstan-deprecation-rules is installed, it reports deprecated usages.
	 */
	private bool $deprecationRulesInstalled;

	public function __construct(
		PHPUnitVersion $PHPUnitVersion,
		TestMethodsHelper $testMethodsHelper,
		bool $deprecationRulesInstalled
	)
	{
		$this->PHPUnitVersion = $PHPUnitVersion;
		$this->testMethodsHelper = $testMethodsHelper;
		$this->deprecationRulesInstalled = $deprecationRulesInstalled;
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

		$errors = [];
		foreach ($reflectionMethod->getAttributesByName('PHPUnit\Framework\Attributes\RequiresPhp') as $attr) {
			$args = $attr->getArguments();
			if (count($args) !== 1) {
				continue;
			}

			if (
				!is_numeric($args[0])
			) {
				continue;
			}

			if ($this->PHPUnitVersion->requiresPhpversionAttributeWithOperator()->yes()) {
				$errors[] = RuleErrorBuilder::message(
					sprintf('Version requirement is missing operator.'),
				)
					->identifier('phpunit.attributeRequiresPhpVersion')
					->build();
			} elseif (
				$this->deprecationRulesInstalled
				&& $this->PHPUnitVersion->deprecatesPhpversionAttributeWithoutOperator()->yes()
			) {
				$errors[] = RuleErrorBuilder::message(
					sprintf('Version requirement without operator is deprecated.'),
				)
					->identifier('phpunit.attributeRequiresPhpVersion')
					->build();
			}

		}

		return $errors;
	}

}
