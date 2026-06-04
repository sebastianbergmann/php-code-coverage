<?php declare(strict_types = 1);

namespace PHPStan\Type\PHPUnit\Assert;

use Closure;
use Countable;
use EmptyIterator;
use PhpParser\Node\Arg;
use PhpParser\Node\Expr;
use PhpParser\Node\Expr\BinaryOp\Identical;
use PhpParser\Node\Expr\BooleanNot;
use PhpParser\Node\Expr\ConstFetch;
use PhpParser\Node\Expr\FuncCall;
use PhpParser\Node\Expr\Instanceof_;
use PhpParser\Node\Name;
use PhpParser\Node\Param;
use PhpParser\Node\Scalar\LNumber;
use PhpParser\Node\Stmt;
use PHPStan\Analyser\Scope;
use PHPStan\Analyser\SpecifiedTypes;
use PHPStan\Analyser\TypeSpecifier;
use PHPStan\Analyser\TypeSpecifierContext;
use ReflectionObject;
use function array_key_exists;
use function count;
use function in_array;
use function strlen;
use function strpos;
use function substr;

class AssertTypeSpecifyingExtensionHelper
{

	/** @var Closure[] */
	private static ?array $resolvers = null;

	/**
	 * Those can specify types correctly, but would produce always-true issue
	 * @var string[]
	 */
	private static array $resolversCausingAlwaysTrue = ['ContainsOnlyInstancesOf', 'ContainsEquals', 'Contains'];

	/**
	 * @param Arg[] $args
	 */
	public static function isSupported(
		string $name,
		array $args
	): bool
	{
		$trimmedName = self::trimName($name);
		$resolvers = self::getExpressionResolvers();

		if (!array_key_exists($trimmedName, $resolvers)) {
			return false;
		}

		$resolver = $resolvers[$trimmedName];
		$resolverReflection = new ReflectionObject($resolver);

		return count($args) >= count($resolverReflection->getMethod('__invoke')->getParameters()) - 1;
	}

	private static function trimName(string $name): string
	{
		if (strpos($name, 'assert') !== 0) {
			return $name;
		}

		$name = substr($name, strlen('assert'));

		if (strpos($name, 'Not') === 0) {
			return substr($name, 3);
		}

		if (strpos($name, 'IsNot') === 0) {
			return 'Is' . substr($name, 5);
		}

		return $name;
	}

	/**
	 * @param Arg[] $args $args
	 */
	public static function specifyTypes(
		TypeSpecifier $typeSpecifier,
		Scope $scope,
		string $name,
		array $args
	): SpecifiedTypes
	{
		$expression = self::createExpression($scope, $name, $args);
		if ($expression === null) {
			return new SpecifiedTypes([], []);
		}

		$bypassAlwaysTrueIssue = in_array(self::trimName($name), self::$resolversCausingAlwaysTrue, true);

		return $typeSpecifier->specifyTypesInCondition(
			$scope,
			$expression,
			TypeSpecifierContext::createTruthy(),
		)->setRootExpr($bypassAlwaysTrueIssue ? new Expr\BinaryOp\BooleanAnd($expression, new Expr\Variable('nonsense')) : $expression);
	}

	/**
	 * @param Arg[] $args
	 */
	private static function createExpression(
		Scope $scope,
		string $name,
		array $args
	): ?Expr
	{
		$trimmedName = self::trimName($name);
		$resolvers = self::getExpressionResolvers();
		$resolver = $resolvers[$trimmedName];
		$expression = $resolver($scope, ...$args);
		if ($expression === null) {
			return null;
		}

		if (strpos($name, 'Not') !== false) {
			$expression = new BooleanNot($expression);
		}

		return $expression;
	}

	/**
	 * @return Closure[]
	 */
	private static function getExpressionResolvers(): array
	{
		if (self::$resolvers === null) {
			self::$resolvers = [
				'Count' => static fn (Scope $scope, Arg $expected, Arg $actual): Identical => new Identical(
					$expected->value,
					new FuncCall(new Name('count'), [$actual]),
				),
				'NotCount' => static fn (Scope $scope, Arg $expected, Arg $actual): BooleanNot => new BooleanNot(
					new Identical(
						$expected->value,
						new FuncCall(new Name('count'), [$actual]),
					),
				),
				'InstanceOf' => static fn (Scope $scope, Arg $class, Arg $object): Instanceof_ => new Instanceof_(
					$object->value,
					$class->value,
				),
				'Same' => static fn (Scope $scope, Arg $expected, Arg $actual): Identical => new Identical(
					$expected->value,
					$actual->value,
				),
				'True' => static fn (Scope $scope, Arg $actual): Identical => new Identical(
					$actual->value,
					new ConstFetch(new Name('true')),
				),
				'False' => static fn (Scope $scope, Arg $actual): Identical => new Identical(
					$actual->value,
					new ConstFetch(new Name('false')),
				),
				'Null' => static fn (Scope $scope, Arg $actual): Identical => new Identical(
					$actual->value,
					new ConstFetch(new Name('null')),
				),
				'Empty' => static fn (Scope $scope, Arg $actual): Expr\BinaryOp\BooleanOr => new Expr\BinaryOp\BooleanOr(
					new Instanceof_($actual->value, new Name(EmptyIterator::class)),
					new Expr\BinaryOp\BooleanOr(
						new Expr\BinaryOp\BooleanAnd(
							new Instanceof_($actual->value, new Name(Countable::class)),
							new Identical(new FuncCall(new Name('count'), [new Arg($actual->value)]), new LNumber(0)),
						),
						new Expr\Empty_($actual->value),
					),
				),
				'IsArray' => static fn (Scope $scope, Arg $actual): FuncCall => new FuncCall(new Name('is_array'), [$actual]),
				'IsBool' => static fn (Scope $scope, Arg $actual): FuncCall => new FuncCall(new Name('is_bool'), [$actual]),
				'IsCallable' => static fn (Scope $scope, Arg $actual): FuncCall => new FuncCall(new Name('is_callable'), [$actual]),
				'IsFloat' => static fn (Scope $scope, Arg $actual): FuncCall => new FuncCall(new Name('is_float'), [$actual]),
				'IsInt' => static fn (Scope $scope, Arg $actual): FuncCall => new FuncCall(new Name('is_int'), [$actual]),
				'IsIterable' => static fn (Scope $scope, Arg $actual): FuncCall => new FuncCall(new Name('is_iterable'), [$actual]),
				'IsNumeric' => static fn (Scope $scope, Arg $actual): FuncCall => new FuncCall(new Name('is_numeric'), [$actual]),
				'IsObject' => static fn (Scope $scope, Arg $actual): FuncCall => new FuncCall(new Name('is_object'), [$actual]),
				'IsResource' => static fn (Scope $scope, Arg $actual): FuncCall => new FuncCall(new Name('is_resource'), [$actual]),
				'IsString' => static fn (Scope $scope, Arg $actual): FuncCall => new FuncCall(new Name('is_string'), [$actual]),
				'IsScalar' => static fn (Scope $scope, Arg $actual): FuncCall => new FuncCall(new Name('is_scalar'), [$actual]),
				'InternalType' => static function (Scope $scope, Arg $type, Arg $value): ?FuncCall {
					$typeNames = $scope->getType($type->value)->getConstantStrings();
					if (count($typeNames) !== 1) {
						return null;
					}

					switch ($typeNames[0]->getValue()) {
						case 'numeric':
							$functionName = 'is_numeric';
							break;
						case 'integer':
						case 'int':
							$functionName = 'is_int';
							break;

						case 'double':
						case 'float':
						case 'real':
							$functionName = 'is_float';
							break;

						case 'string':
							$functionName = 'is_string';
							break;

						case 'boolean':
						case 'bool':
							$functionName = 'is_bool';
							break;

						case 'scalar':
							$functionName = 'is_scalar';
							break;

						case 'null':
							$functionName = 'is_null';
							break;

						case 'array':
							$functionName = 'is_array';
							break;

						case 'object':
							$functionName = 'is_object';
							break;

						case 'resource':
							$functionName = 'is_resource';
							break;

						case 'callable':
							$functionName = 'is_callable';
							break;
						default:
							return null;
					}

					return new FuncCall(
						new Name($functionName),
						[
							$value,
						],
					);
				},
				'ArrayHasKey' => static fn (Scope $scope, Arg $key, Arg $array): Expr => new Expr\BinaryOp\BooleanOr(
					new Expr\BinaryOp\BooleanAnd(
						new Expr\Instanceof_($array->value, new Name('ArrayAccess')),
						new Expr\MethodCall($array->value, 'offsetExists', [$key]),
					),
					new FuncCall(new Name('array_key_exists'), [$key, $array]),
				),
				'ObjectHasAttribute' => static fn (Scope $scope, Arg $property, Arg $object): FuncCall => new FuncCall(new Name('property_exists'), [$object, $property]),
				'ObjectHasProperty' => static fn (Scope $scope, Arg $property, Arg $object): FuncCall => new FuncCall(new Name('property_exists'), [$object, $property]),
				'Contains' => static fn (Scope $scope, Arg $needle, Arg $haystack): Expr => new Expr\BinaryOp\BooleanOr(
					new Expr\Instanceof_($haystack->value, new Name('Traversable')),
					new FuncCall(new Name('in_array'), [$needle, $haystack, new Arg(new ConstFetch(new Name('true')))]),
				),
				'ContainsEquals' => static fn (Scope $scope, Arg $needle, Arg $haystack): Expr => new Expr\BinaryOp\BooleanOr(
					new Expr\Instanceof_($haystack->value, new Name('Traversable')),
					new Expr\BinaryOp\BooleanAnd(
						new Expr\BooleanNot(new Expr\Empty_($haystack->value)),
						new FuncCall(new Name('in_array'), [$needle, $haystack, new Arg(new ConstFetch(new Name('false')))]),
					),
				),
				'ContainsOnlyInstancesOf' => static fn (Scope $scope, Arg $className, Arg $haystack): Expr => new Expr\BinaryOp\BooleanOr(
					new Expr\Instanceof_($haystack->value, new Name('Traversable')),
					new Identical(
						$haystack->value,
						new FuncCall(new Name('array_filter'), [
							$haystack,
							new Arg(new Expr\Closure([
								'static' => true,
								'params' => [
									new Param(new Expr\Variable('_')),
								],
								'stmts' => [
									new Stmt\Return_(
										new FuncCall(new Name('is_a'), [new Arg(new Expr\Variable('_')), $className]),
									),
								],
							])),
						]),
					),
				),
			];
		}

		return self::$resolvers;
	}

}
