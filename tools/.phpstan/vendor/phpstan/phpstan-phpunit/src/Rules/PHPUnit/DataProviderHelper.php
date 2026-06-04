<?php declare(strict_types = 1);

namespace PHPStan\Rules\PHPUnit;

use PhpParser\Comment\Doc;
use PhpParser\Modifiers;
use PhpParser\Node\Attribute;
use PhpParser\Node\Expr\ClassConstFetch;
use PhpParser\Node\Name;
use PhpParser\Node\Scalar\String_;
use PhpParser\Node\Stmt\ClassMethod;
use PhpParser\NodeFinder;
use PHPStan\Analyser\Scope;
use PHPStan\BetterReflection\Reflection\ReflectionMethod;
use PHPStan\Parser\Parser;
use PHPStan\PhpDoc\ResolvedPhpDocBlock;
use PHPStan\PhpDocParser\Ast\PhpDoc\PhpDocTagNode;
use PHPStan\Reflection\ClassReflection;
use PHPStan\Reflection\MissingMethodFromReflectionException;
use PHPStan\Reflection\ReflectionProvider;
use PHPStan\Rules\IdentifierRuleError;
use PHPStan\Rules\RuleErrorBuilder;
use PHPStan\Type\FileTypeMapper;
use function array_merge;
use function count;
use function explode;
use function preg_match;
use function sprintf;

class DataProviderHelper
{

	private ReflectionProvider $reflectionProvider;

	private FileTypeMapper $fileTypeMapper;

	private Parser $parser;

	private PHPUnitVersion $PHPUnitVersion;

	public function __construct(
		ReflectionProvider $reflectionProvider,
		FileTypeMapper $fileTypeMapper,
		Parser $parser,
		PHPUnitVersion $PHPUnitVersion
	)
	{
		$this->reflectionProvider = $reflectionProvider;
		$this->fileTypeMapper = $fileTypeMapper;
		$this->parser = $parser;
		$this->PHPUnitVersion = $PHPUnitVersion;
	}

	/**
	 * @param ReflectionMethod|ClassMethod $testMethod
	 *
	 * @return iterable<array{ClassReflection|null, string, int}>
	 */
	public function getDataProviderMethods(
		Scope $scope,
		$testMethod,
		ClassReflection $classReflection
	): iterable
	{
		yield from $this->yieldDataProviderAnnotations($testMethod, $scope, $classReflection);

		if (!$this->PHPUnitVersion->supportsDataProviderAttribute()->yes()) {
			return;
		}

		yield from $this->yieldDataProviderAttributes($testMethod, $classReflection);
	}

	/**
	 * @return array<PhpDocTagNode>
	 */
	private function getDataProviderAnnotations(?ResolvedPhpDocBlock $phpDoc): array
	{
		if ($phpDoc === null) {
			return [];
		}

		$phpDocNodes = $phpDoc->getPhpDocNodes();

		$annotations = [];

		foreach ($phpDocNodes as $docNode) {
			$annotations = array_merge(
				$annotations,
				$docNode->getTagsByName('@dataProvider'),
			);
		}

		return $annotations;
	}

	/**
	 * @return list<IdentifierRuleError> errors
	 */
	public function processDataProvider(
		string $dataProviderValue,
		?ClassReflection $classReflection,
		string $methodName,
		int $lineNumber,
		bool $checkFunctionNameCase,
		bool $deprecationRulesInstalled
	): array
	{
		if ($classReflection === null) {
			return [
				RuleErrorBuilder::message(sprintf(
					'@dataProvider %s related class not found.',
					$dataProviderValue,
				))
					->line($lineNumber)
					->identifier('phpunit.dataProviderClass')
					->build(),
			];
		}

		try {
			$dataProviderMethodReflection = $classReflection->getNativeMethod($methodName);
		} catch (MissingMethodFromReflectionException $missingMethodFromReflectionException) {
			return [
				RuleErrorBuilder::message(sprintf(
					'@dataProvider %s related method not found.',
					$dataProviderValue,
				))
					->line($lineNumber)
					->identifier('phpunit.dataProviderMethod')
					->build(),
			];
		}

		$errors = [];

		if ($checkFunctionNameCase && $methodName !== $dataProviderMethodReflection->getName()) {
			$errors[] = RuleErrorBuilder::message(sprintf(
				'@dataProvider %s related method is used with incorrect case: %s.',
				$dataProviderValue,
				$dataProviderMethodReflection->getName(),
			))
				->line($lineNumber)
				->identifier('method.nameCase')
				->build();
		}

		if (!$dataProviderMethodReflection->isPublic()) {
			$errors[] = RuleErrorBuilder::message(sprintf(
				'@dataProvider %s related method must be public.',
				$dataProviderValue,
			))
				->line($lineNumber)
				->identifier('phpunit.dataProviderPublic')
				->build();
		}

		if (
			$deprecationRulesInstalled
			&& $this->PHPUnitVersion->requiresStaticDataProviders()->yes()
			&& !$dataProviderMethodReflection->isStatic()
		) {
			$errorBuilder = RuleErrorBuilder::message(sprintf(
				'@dataProvider %s related method must be static in PHPUnit 10 and newer.',
				$dataProviderValue,
			))
				->line($lineNumber)
				->identifier('phpunit.dataProviderStatic');

			$dataProviderMethodReflectionDeclaringClass = $dataProviderMethodReflection->getDeclaringClass();
			if ($dataProviderMethodReflectionDeclaringClass->getFileName() !== null) {
				$stmts = $this->parser->parseFile($dataProviderMethodReflectionDeclaringClass->getFileName());
				$nodeFinder = new NodeFinder();
				/** @var ClassMethod|null $methodNode */
				$methodNode = $nodeFinder->findFirst($stmts, static fn ($node) => $node instanceof ClassMethod && $node->name->toString() === $dataProviderMethodReflection->getName());
				if ($methodNode !== null) {
					$errorBuilder->fixNode($methodNode, static function (ClassMethod $methodNode) {
						$methodNode->flags |= Modifiers::STATIC;

						return $methodNode;
					});
				}
			}
			$errors[] = $errorBuilder->build();
		}

		return $errors;
	}

	private function getDataProviderAnnotationValue(PhpDocTagNode $phpDocTag): ?string
	{
		if (preg_match('/^[^ \t]+/', (string) $phpDocTag->value, $matches) !== 1) {
			return null;
		}

		return $matches[0];
	}

	/**
	 * @return array{ClassReflection|null, string}
	 */
	private function parseDataProviderAnnotationValue(Scope $scope, string $dataProviderValue): array
	{
		$parts = explode('::', $dataProviderValue, 2);
		if (count($parts) <= 1) {
			return [$scope->getClassReflection(), $dataProviderValue];
		}

		if ($this->reflectionProvider->hasClass($parts[0])) {
			return [$this->reflectionProvider->getClass($parts[0]), $parts[1]];
		}

		return [null, $dataProviderValue];
	}

	/**
	 * @return array<string, array{(ClassReflection|null), string, int}>|null
	 */
	private function parseDataProviderExternalAttribute(Attribute $attribute): ?array
	{
		if (count($attribute->args) !== 2) {
			return null;
		}
		$methodNameArg = $attribute->args[1]->value;
		if (!$methodNameArg instanceof String_) {
			return null;
		}
		$classNameArg = $attribute->args[0]->value;
		if ($classNameArg instanceof ClassConstFetch && $classNameArg->class instanceof Name) {
			$className = $classNameArg->class->toString();
		} elseif ($classNameArg instanceof String_) {
			$className = $classNameArg->value;
		} else {
			return null;
		}

		$dataProviderClassReflection = null;
		if ($this->reflectionProvider->hasClass($className)) {
			$dataProviderClassReflection = $this->reflectionProvider->getClass($className);
			$className = $dataProviderClassReflection->getName();
		}

		return [
			sprintf('%s::%s', $className, $methodNameArg->value) => [
				$dataProviderClassReflection,
				$methodNameArg->value,
				$attribute->getStartLine(),
			],
		];
	}

	/**
	 * @return array<string, array{ClassReflection, string, int}>|null
	 */
	private function parseDataProviderAttribute(Attribute $attribute, ClassReflection $classReflection): ?array
	{
		if (count($attribute->args) !== 1) {
			return null;
		}
		$methodNameArg = $attribute->args[0]->value;
		if (!$methodNameArg instanceof String_) {
			return null;
		}

		return [
			$methodNameArg->value => [
				$classReflection,
				$methodNameArg->value,
				$attribute->getStartLine(),
			],
		];
	}

	/**
	 * @param ReflectionMethod|ClassMethod $node
	 *
	 * @return iterable<array{ClassReflection|null, string, int}>
	 */
	private function yieldDataProviderAttributes($node, ClassReflection $classReflection): iterable
	{
		if (
			$node instanceof ReflectionMethod
		) {
			foreach ($node->getAttributesByName('PHPUnit\Framework\Attributes\DataProvider') as $attr) {
				$args = $attr->getArguments();
				if (count($args) !== 1) {
					continue;
				}

				$startLine = $node->getStartLine();

				yield [$classReflection, $args[0], $startLine];
			}

			return;
		}

		foreach ($node->attrGroups as $attrGroup) {
			foreach ($attrGroup->attrs as $attr) {
				$dataProviderMethod = null;
				if ($attr->name->toLowerString() === 'phpunit\\framework\\attributes\\dataprovider') {
					$dataProviderMethod = $this->parseDataProviderAttribute($attr, $classReflection);
				} elseif ($attr->name->toLowerString() === 'phpunit\\framework\\attributes\\dataproviderexternal') {
					$dataProviderMethod = $this->parseDataProviderExternalAttribute($attr);
				}
				if ($dataProviderMethod === null) {
					continue;
				}

				yield from $dataProviderMethod;
			}
		}
	}

	/**
	 * @param ReflectionMethod|ClassMethod $node
	 *
	 * @return iterable<array{ClassReflection|null, string, int}>
	 */
	private function yieldDataProviderAnnotations($node, Scope $scope, ClassReflection $classReflection): iterable
	{
		$docComment = $node->getDocComment();
		if ($docComment === null) {
			return;
		}

		$methodPhpDoc = $this->fileTypeMapper->getResolvedPhpDoc(
			$scope->getFile(),
			$classReflection->getName(),
			$scope->isInTrait() ? $scope->getTraitReflection()->getName() : null,
			$node instanceof ClassMethod ? $node->name->toString() : $node->getName(),
			$docComment instanceof Doc ? $docComment->getText() : $docComment,
		);
		foreach ($this->getDataProviderAnnotations($methodPhpDoc) as $annotation) {
			$dataProviderValue = $this->getDataProviderAnnotationValue($annotation);
			if ($dataProviderValue === null) {
				// Missing value is already handled in NoMissingSpaceInMethodAnnotationRule
				continue;
			}

			$startLine = $node->getStartLine();
			$dataProviderMethod = $this->parseDataProviderAnnotationValue($scope, $dataProviderValue);
			$dataProviderMethod[] = $startLine;

			yield $dataProviderValue => $dataProviderMethod;
		}
	}

}
