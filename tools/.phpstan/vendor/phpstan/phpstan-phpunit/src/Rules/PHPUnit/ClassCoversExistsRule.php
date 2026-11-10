<?php declare(strict_types = 1);

namespace PHPStan\Rules\PHPUnit;

use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\Node\InClassNode;
use PHPStan\Reflection\ReflectionProvider;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use PHPUnit\Framework\TestCase;
use function array_merge;
use function array_shift;
use function count;
use function sprintf;

/**
 * @implements Rule<InClassNode>
 */
class ClassCoversExistsRule implements Rule
{

	/**
	 * Covers helper.
	 *
	 */
	private CoversHelper $coversHelper;

	/**
	 * Reflection provider.
	 *
	 */
	private ReflectionProvider $reflectionProvider;

	public function __construct(
		CoversHelper $coversHelper,
		ReflectionProvider $reflectionProvider
	)
	{
		$this->reflectionProvider = $reflectionProvider;
		$this->coversHelper = $coversHelper;
	}

	public function getNodeType(): string
	{
		return InClassNode::class;
	}

	public function processNode(Node $node, Scope $scope): array
	{
		$classReflection = $node->getClassReflection();

		if (!$classReflection->is(TestCase::class)) {
			return [];
		}

		$classPhpDoc = $classReflection->getResolvedPhpDoc();
		[$classCovers, $classCoversDefaultClasses] = $this->coversHelper->getCoverAnnotations($classPhpDoc);

		if (count($classCoversDefaultClasses) >= 2) {
			return [
				RuleErrorBuilder::message(sprintf(
					'@coversDefaultClass is defined multiple times.',
				))->identifier('phpunit.coversDuplicate')->build(),
			];
		}

		$errors = [];
		$coversDefaultClass = array_shift($classCoversDefaultClasses);

		if ($coversDefaultClass !== null) {
			$className = (string) $coversDefaultClass->value;
			if (!$this->reflectionProvider->hasClass($className)) {
				$errors[] = RuleErrorBuilder::message(sprintf(
					'@coversDefaultClass references an invalid class %s.',
					$className,
				))->identifier('phpunit.coversClass')->build();
			}
		}

		foreach ($classCovers as $covers) {
			$errors = array_merge(
				$errors,
				$this->coversHelper->processCovers($node, $covers, null),
			);
		}

		return $errors;
	}

}
