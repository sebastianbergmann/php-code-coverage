<?php declare(strict_types = 1);

namespace PHPStan\Rules\PHPUnit;

use PharIo\Version\UnsupportedVersionConstraintException;
use PharIo\Version\Version;
use PharIo\Version\VersionConstraintParser;
use PHPStan\Analyser\Scope;
use PHPStan\BetterReflection\Reflection\ReflectionAttribute;
use PHPStan\Php\PhpMinorVersionIterator;
use PHPStan\Php\PhpVersion;
use PHPStan\Rules\IdentifierRuleError;
use PHPStan\Rules\RuleErrorBuilder;
use PHPStan\Type\Constant\ConstantIntegerType;
use PHPStan\Type\IntegerRangeType;
use function count;
use function is_numeric;
use function preg_match;
use function sprintf;
use function substr_count;
use function version_compare;

final class AttributeVersionRequirementHelper
{

	private const VERSION_COMPARISON = "/(?P<operator>!=|<|<=|<>|=|==|>|>=)?\s*(?P<version>[\d\.-]+(dev|(RC|alpha|beta)[\d\.])?)[ \t]*\r?$/m";

	private PHPUnitVersion $PHPUnitVersion;

	private PhpVersion $fallbackPhpVersion;

	/**
	 * When phpstan-deprecation-rules is installed, rule reports deprecated usages.
	 */
	private bool $deprecationRulesInstalled;

	/**
	 * Whether warnings about incomplete versions are allowed to be emitted
	 */
	private bool $warnAboutIncompleteVersion;

	private bool $bleedingEdge;

	public function __construct(
		PHPUnitVersion $PHPUnitVersion,
		bool $deprecationRulesInstalled,
		PhpVersion $phpVersion,
		bool $bleedingEdge,
		bool $warnAboutIncompleteVersion = true
	)
	{
		$this->PHPUnitVersion = $PHPUnitVersion;
		$this->deprecationRulesInstalled = $deprecationRulesInstalled;
		$this->fallbackPhpVersion = $phpVersion;
		$this->warnAboutIncompleteVersion = $warnAboutIncompleteVersion;
		$this->bleedingEdge = $bleedingEdge;
	}

	/**
	 * @param array<ReflectionAttribute> $attributes
	 *
	 * @return list<IdentifierRuleError>
	 */
	public function checkVersionRequirement(array $attributes, Scope $scope): array
	{
		$phpstanPharIoVersions = $this->getAnalyzedPhpVersions($scope);
		if ($phpstanPharIoVersions === []) {
			return [];
		}

		$errors = [];
		$parser = new VersionConstraintParser();
		foreach ($attributes as $attr) {
			$args = $attr->getArguments();
			if (count($args) !== 1) {
				continue;
			}

			// the following block is mimicing PHPUnit version parsing
			// see https://github.com/sebastianbergmann/phpunit/blob/43c2cd7b96ee1e800b35e4df23b419a88b53111d/src/Metadata/Version/Requirement.php

			$versionRequirement = $args[0];

			if ($this->warnAboutIncompleteVersion($versionRequirement)) {
				$errors[] = RuleErrorBuilder::message(
					sprintf('Version requirement is incomplete.'),
				)
					->identifier('phpunit.attributeRequiresPhpVersion')
					->build();
			}

			if (
				!is_numeric($versionRequirement)
			) {
				if (!$this->bleedingEdge) {
					continue;
				}

				try {
					// check composer like version constraints, e.g. ^1  or ~2
					$testPhpVersionConstraint = $parser->parse($versionRequirement);

					foreach ($phpstanPharIoVersions as $pharIoVersion) {
						if ($testPhpVersionConstraint->complies($pharIoVersion)) {
							// one of the versions within range matched, check next attribute
							continue 2;
						}
					}
				} catch (UnsupportedVersionConstraintException $e) {
					// test php-src builtin operators as in version_compare()
					if (preg_match(self::VERSION_COMPARISON, $versionRequirement, $matches) <= 0) {
						$errors[] = RuleErrorBuilder::message(
							sprintf($e->getMessage()),
						)
							->identifier('phpunit.attributeRequiresPhpVersion')
							->build();

						continue;
					}

					$operator = $matches['operator'] !== '' ? $matches['operator'] : '>=';

					foreach ($phpstanPharIoVersions as $pharIoVersion) {
						if (version_compare($pharIoVersion->getVersionString(), $matches['version'], $operator)) {
							// one of the versions within range matched, check next attribute
							continue 2;
						}
					}
				}

				$errors[] = RuleErrorBuilder::message(
					sprintf('Version requirement will always evaluate to false.'),
				)
					->identifier('phpunit.attributeRequiresPhpVersion')
					->build();

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

	/**
	 * @return Version[]
	 */
	private function getAnalyzedPhpVersions(Scope $scope): array
	{
		$scopePhpVersion = $scope->getPhpVersion()->getType();
		if ($scopePhpVersion instanceof ConstantIntegerType) {
			$v = new PhpVersion($scopePhpVersion->getValue());
			return [new Version($v->getVersionString())];
		} elseif ($scopePhpVersion instanceof IntegerRangeType) {
			if ($scopePhpVersion->getMin() === null || $scopePhpVersion->getMax() === null) {
				return [];
			}

			$versions = [];
			$minorVersionIterator = new PhpMinorVersionIterator(
				new PhpVersion($scopePhpVersion->getMin()),
				new PhpVersion($scopePhpVersion->getMax()),
			);
			foreach ($minorVersionIterator as $phpstanVersion) {
				$versions[] = new Version($phpstanVersion->getVersionString());
			}
			return $versions;
		}

		return [new Version($this->fallbackPhpVersion->getVersionString())];
	}

	// see https://github.com/sebastianbergmann/phpunit/issues/6451
	private function warnAboutIncompleteVersion(string $versionRequirement): bool
	{
		if (!$this->bleedingEdge) {
			return false;
		}

		if (!$this->warnAboutIncompleteVersion) {
			return false;
		}

		if (!$this->PHPUnitVersion->warnsAboutIncompleteVersion()->yes()) {
			return false;
		}

		return substr_count($versionRequirement, '.') !== 2;
	}

}
