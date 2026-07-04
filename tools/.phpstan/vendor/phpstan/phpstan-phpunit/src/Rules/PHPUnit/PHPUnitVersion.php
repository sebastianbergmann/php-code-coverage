<?php declare(strict_types = 1);

namespace PHPStan\Rules\PHPUnit;

use PharIo\Version\Version;
use PHPStan\TrinaryLogic;
use function sprintf;

class PHPUnitVersion
{

	private ?int $majorVersion;

	private ?int $minorVersion;

	public function __construct(?int $majorVersion, ?int $minorVersion)
	{
		$this->majorVersion = $majorVersion;
		$this->minorVersion = $minorVersion;
	}

	/**
	 * @return array{}|array{Version, Version}
	 */
	public function getPharIoVersions(): array
	{
		if ($this->majorVersion === null || $this->minorVersion === null) {
			return [];
		}

		return [
			new Version(sprintf('%d.%d.0', $this->majorVersion, $this->minorVersion)),
			new Version(sprintf('%d.%d.99', $this->majorVersion, $this->minorVersion)),
		];
	}

	public function supportsDataProviderAttribute(): TrinaryLogic
	{
		if ($this->majorVersion === null) {
			return TrinaryLogic::createMaybe();
		}
		return TrinaryLogic::createFromBoolean($this->majorVersion >= 10);
	}

	public function supportsTestAttribute(): TrinaryLogic
	{
		if ($this->majorVersion === null) {
			return TrinaryLogic::createMaybe();
		}
		return TrinaryLogic::createFromBoolean($this->majorVersion >= 10);
	}

	public function requiresStaticDataProviders(): TrinaryLogic
	{
		if ($this->majorVersion === null) {
			return TrinaryLogic::createMaybe();
		}
		return TrinaryLogic::createFromBoolean($this->majorVersion >= 10);
	}

	public function supportsNamedArgumentsInDataProvider(): TrinaryLogic
	{
		if ($this->majorVersion === null) {
			return TrinaryLogic::createMaybe();
		}
		return TrinaryLogic::createFromBoolean($this->majorVersion >= 11);
	}

	public function requiresPhpversionAttributeWithOperator(): TrinaryLogic
	{
		if ($this->majorVersion === null) {
			return TrinaryLogic::createMaybe();
		}
		return TrinaryLogic::createFromBoolean($this->majorVersion >= 13);
	}

	public function deprecatesPhpversionAttributeWithoutOperator(): TrinaryLogic
	{
		return $this->minVersion(12, 4);
	}

	public function warnsAboutIncompleteVersion(): TrinaryLogic
	{
		return $this->minVersion(12, 5);
	}

	private function minVersion(int $major, int $minor): TrinaryLogic
	{
		if ($this->majorVersion === null || $this->minorVersion === null) {
			return TrinaryLogic::createMaybe();
		}

		if ($this->majorVersion > $major) {
			return TrinaryLogic::createYes();
		}

		if ($this->majorVersion === $major && $this->minorVersion >= $minor) {
			return TrinaryLogic::createYes();
		}

		return TrinaryLogic::createNo();
	}

}
