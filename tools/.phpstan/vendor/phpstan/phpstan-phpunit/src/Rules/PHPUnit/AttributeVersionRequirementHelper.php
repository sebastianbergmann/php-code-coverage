<?php declare(strict_types = 1);

namespace PHPStan\Rules\PHPUnit;

use PharIo\Version\UnsupportedVersionConstraintException;
use PharIo\Version\Version;
use PharIo\Version\VersionConstraintParser;
use PHPStan\Analyser\Scope;
use PHPStan\BetterReflection\Reflection\ReflectionAttribute;
use PHPStan\Php\ConfiguredPhpVersionRangeHelper;
use PHPStan\Php\PhpMinorVersionIterator;
use PHPStan\Rules\IdentifierRuleError;
use PHPStan\Rules\RuleErrorBuilder;
use PHPStan\ShouldNotHappenException;
use function count;
use function is_numeric;
use function preg_match;
use function sprintf;
use function strpos;
use function substr_count;
use function version_compare;

final class AttributeVersionRequirementHelper
{

	private const VERSION_COMPARISON = "/(?P<operator>!=|<|<=|<>|=|==|>|>=)?\s*(?P<version>[\d\.-]+(dev|(RC|alpha|beta)[\d\.])?)[ \t]*\r?$/m";

	private PHPUnitVersion $PHPUnitVersion;

	/**
	 * When phpstan-deprecation-rules is installed, rule reports deprecated usages.
	 */
	private bool $deprecationRulesInstalled;

	/**
	 * Whether warnings about incomplete versions are allowed to be emitted
	 */
	private bool $warnAboutIncompleteVersion;

	private bool $bleedingEdge;

	private ConfiguredPhpVersionRangeHelper $phpVersionRangeHelper;

	public function __construct(
		PHPUnitVersion $PHPUnitVersion,
		ConfiguredPhpVersionRangeHelper $phpVersionRangeHelper,
		bool $deprecationRulesInstalled = false,
		bool $bleedingEdge = false,
		bool $warnAboutIncompleteVersion = true
	)
	{
		$this->PHPUnitVersion = $PHPUnitVersion;
		$this->deprecationRulesInstalled = $deprecationRulesInstalled;
		$this->warnAboutIncompleteVersion = $warnAboutIncompleteVersion;
		$this->bleedingEdge = $bleedingEdge;
		$this->phpVersionRangeHelper = $phpVersionRangeHelper;
	}

	/**
	 * @param array<ReflectionAttribute> $attributes
	 *
	 * @return list<IdentifierRuleError>
	 */
	public function checkVersionRequirement(array $attributes, Scope $scope): array
	{
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
					sprintf('Version requirement %s is incomplete. Expect a version composed of major, minor and patch.', $versionRequirement),
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

				$pharIoVersions = strpos($attr->getName(), 'RequiresPhpunit') !== false
					? $this->PHPUnitVersion->getPharIoVersions()
					: $this->getAnalyzedPhpVersions();
				if ($pharIoVersions === []) {
					continue;
				}

				try {
					// check composer like version constraints, e.g. ^1  or ~2
					$testPhpVersionConstraint = $parser->parse($versionRequirement);

					foreach ($pharIoVersions as $pharIoVersion) {
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

					foreach ($pharIoVersions as $pharIoVersion) {
						if (version_compare($pharIoVersion->getVersionString(), $matches['version'], $operator)) {
							// one of the versions within range matched, check next attribute
							continue 2;
						}
					}
				}

				if (count($pharIoVersions) < 2) {
					throw new ShouldNotHappenException();
				}

				if (strpos($attr->getName(), 'RequiresPhpunit') !== false) {
					$tip = 'PHPUnit version inferred from composer.json requirements.';
				} else {
					$tip = 'PHP version for analysis inferred from NEON config phpVersion or composer.json requirements. Invoke PHPStan with -vvv to get more details.';
				}

				$errors[] = RuleErrorBuilder::message(
					sprintf(
						'Version requirement %s does not match %s...%s.',
						$versionRequirement,
						$pharIoVersions[0]->getVersionString(),
						$pharIoVersions[count($pharIoVersions) - 1]->getVersionString(),
					),
				)
					->tip($tip)
					->identifier('phpunit.attributeRequiresPhpVersion')
					->build();

				continue;
			}

			if ($this->PHPUnitVersion->requiresPhpversionAttributeWithOperator()->yes()) {
				$errors[] = RuleErrorBuilder::message(
					sprintf('Version requirement %s is missing operator.', $versionRequirement),
				)
					->identifier('phpunit.attributeRequiresPhpVersion')
					->build();
			} elseif (
				$this->deprecationRulesInstalled
				&& $this->PHPUnitVersion->deprecatesPhpversionAttributeWithoutOperator()->yes()
			) {
				$errors[] = RuleErrorBuilder::message(
					sprintf('Version requirement %s without operator is deprecated.', $versionRequirement),
				)
					->identifier('phpunit.attributeRequiresPhpVersion')
					->build();
			}
		}
		return $errors;
	}

	/**
	 * @return list<Version>
	 */
	private function getAnalyzedPhpVersions(): array
	{
		// @phpstan-ignore phpstanApi.method
		[$minVersion, $maxVersion] = $this->phpVersionRangeHelper->getVersionRange();
		if ($minVersion !== null && $maxVersion !== null) {
			$versions = [];
			$minorVersionIterator = new PhpMinorVersionIterator(
				$minVersion,
				$maxVersion,
			);
			foreach ($minorVersionIterator as $phpstanVersion) {
				$versions[] = new Version($phpstanVersion->getVersionString());
			}
			return $versions;
		}

		return [];
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
