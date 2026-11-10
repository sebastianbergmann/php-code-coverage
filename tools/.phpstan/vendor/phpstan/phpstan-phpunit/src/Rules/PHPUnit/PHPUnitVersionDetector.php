<?php declare(strict_types = 1);

namespace PHPStan\Rules\PHPUnit;

use PHPUnit\Framework\TestCase;
use ReflectionClass;
use ReflectionException;
use function dirname;
use function explode;
use function file_get_contents;
use function json_decode;

class PHPUnitVersionDetector
{

	public function createPHPUnitVersion(): PHPUnitVersion
	{
		$file = false;
		$majorVersion = null;
		$minorVersion = null;

		try {
			// uses runtime reflection to reduce unnecessary work while bootstrapping PHPStan.
			// static reflection would need to AST parse and build up reflection for a lot of files otherwise.
			$reflection = new ReflectionClass(TestCase::class);
			$file = $reflection->getFileName();
		} catch (ReflectionException $e) {
			// PHPUnit might not be installed
		}

		if ($file !== false) {
			$phpUnitRoot = dirname($file, 3);
			$phpUnitComposer = $phpUnitRoot . '/composer.json';

			$composerJson = @file_get_contents($phpUnitComposer);
			if ($composerJson !== false) {
				$json = json_decode($composerJson, true);
				$version = $json['extra']['branch-alias']['dev-main'] ?? null;
				if ($version !== null) {
					$versionParts = explode('.', $version);
					$majorVersion = (int) $versionParts[0];
					$minorVersion = (int) $versionParts[1];
				}
			}
		}

		return new PHPUnitVersion($majorVersion, $minorVersion);
	}

}
