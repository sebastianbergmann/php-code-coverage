<?php declare(strict_types = 1);

namespace PHPStan\Rules\PHPUnit;

use PHPStan\Analyser\Scope;
use PHPStan\BetterReflection\Reflection\ReflectionMethod;
use PHPStan\PhpDoc\ResolvedPhpDocBlock;
use PHPStan\Reflection\ClassReflection;
use PHPStan\Reflection\MethodReflection;
use PHPStan\Type\FileTypeMapper;
use PHPUnit\Framework\TestCase;
use function array_key_exists;
use function str_starts_with;
use function strtolower;

final class TestMethodsHelper
{

	private FileTypeMapper $fileTypeMapper;

	private PHPUnitVersion $PHPUnitVersion;

	/** @var array<string, array<ReflectionMethod>> */
	private array $methodCache = [];

	public function __construct(
		FileTypeMapper $fileTypeMapper,
		PHPUnitVersion $PHPUnitVersion
	)
	{
		$this->fileTypeMapper = $fileTypeMapper;
		$this->PHPUnitVersion = $PHPUnitVersion;
	}

	public function getTestMethodReflection(ClassReflection $classReflection, MethodReflection $methodReflection, Scope $scope): ?ReflectionMethod
	{
		foreach ($this->getTestMethods($classReflection, $scope) as $testMethod) {
			if ($testMethod->getName() === $methodReflection->getName()) {
				return $testMethod;
			}
		}

		return null;
	}

	/**
	 * @return array<ReflectionMethod>
	 */
	public function getTestMethods(ClassReflection $classReflection, Scope $scope): array
	{
		$className = $classReflection->getName();
		if (array_key_exists($className, $this->methodCache)) {
			return $this->methodCache[$className];
		}
		if (!$classReflection->is(TestCase::class)) {
			return $this->methodCache[$className] = [];
		}

		$testMethods = [];
		foreach ($classReflection->getNativeReflection()->getBetterReflection()->getImmediateMethods() as $reflectionMethod) {
			if (!$reflectionMethod->isPublic()) {
				continue;
			}

			if (str_starts_with(strtolower($reflectionMethod->getName()), 'test')) {
				$testMethods[] = $reflectionMethod;
				continue;
			}

			$docComment = $reflectionMethod->getDocComment();
			if ($docComment !== null) {
				$methodPhpDoc = $this->fileTypeMapper->getResolvedPhpDoc(
					$scope->getFile(),
					$className,
					$scope->isInTrait() ? $scope->getTraitReflection()->getName() : null,
					$reflectionMethod->getName(),
					$docComment,
				);

				if ($this->hasTestAnnotation($methodPhpDoc)) {
					$testMethods[] = $reflectionMethod;
					continue;
				}
			}

			if ($this->PHPUnitVersion->supportsTestAttribute()->no()) {
				continue;
			}

			$testAttributes = $reflectionMethod->getAttributesByName('PHPUnit\Framework\Attributes\Test'); // @phpstan-ignore argument.type
			if ($testAttributes === []) {
				continue;
			}

			$testMethods[] = $reflectionMethod;
		}

		return $this->methodCache[$className] = $testMethods;
	}

	private function hasTestAnnotation(?ResolvedPhpDocBlock $phpDoc): bool
	{
		if ($phpDoc === null) {
			return false;
		}

		$phpDocNodes = $phpDoc->getPhpDocNodes();

		foreach ($phpDocNodes as $docNode) {
			$tags = $docNode->getTagsByName('@test');
			if ($tags !== []) {
				return true;
			}
		}

		return false;
	}

}
