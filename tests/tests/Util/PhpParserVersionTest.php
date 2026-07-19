<?php declare(strict_types=1);
/*
 * This file is part of phpunit/php-code-coverage.
 *
 * (c) Sebastian Bergmann <sebastian@phpunit.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace SebastianBergmann\CodeCoverage\Util;

use function strtr;
use Composer\Autoload\ClassLoader;
use Composer\InstalledVersions;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Small;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

#[CoversClass(PhpParserVersion::class)]
#[Small]
final class PhpParserVersionTest extends TestCase
{
    public function testIdentifiesVersionOfPhpParserInstalledThroughComposer(): void
    {
        $version = PhpParserVersion::id();

        $this->assertNotSame('unknown', $version);

        $prettyVersion = InstalledVersions::getPrettyVersion('nikic/php-parser');

        if ($prettyVersion === null || $prettyVersion === '') {
            $this->fail('Version of nikic/php-parser cannot be determined using Composer\'s runtime API');
        }

        $this->assertStringStartsWith($prettyVersion, $version);
    }

    public function testVersionOfPhpParserInstalledThroughComposerIsExactlyKnown(): void
    {
        $this->assertTrue(PhpParserVersion::isExact());
    }

    public function testVersionReportedByComposerWithSourceReferenceIsExact(): void
    {
        [$version, $exact] = $this->detect();

        $prettyVersion = InstalledVersions::getPrettyVersion('nikic/php-parser');
        $reference     = InstalledVersions::getReference('nikic/php-parser');

        if ($prettyVersion === null || $prettyVersion === '' || $reference === null) {
            $this->fail('Version of nikic/php-parser cannot be determined using Composer\'s runtime API');
        }

        $this->assertSame($prettyVersion . ' (' . $reference . ')', $version);
        $this->assertTrue($exact);
    }

    public function testReleaseVersionReportedByComposerWithoutSourceReferenceIsExact(): void
    {
        [$version, $exact] = $this->detectForPhpParserPackageData('5.6.1', null);

        $this->assertSame('5.6.1', $version);
        $this->assertTrue($exact);
    }

    public function testVersionWithEmbeddedSourceReferenceReportedByComposerWithoutSourceReferenceIsExact(): void
    {
        [$version, $exact] = $this->detectForPhpParserPackageData('5.6.1 (abc1234)', null);

        $this->assertSame('5.6.1 (abc1234)', $version);
        $this->assertTrue($exact);
    }

    public function testBranchVersionReportedByComposerWithoutSourceReferenceIsNotExact(): void
    {
        [$version, $exact] = $this->detectForPhpParserPackageData('dev-main', null);

        $this->assertSame('dev-main', $version);
        $this->assertFalse($exact);
    }

    public function testBranchAliasVersionReportedByComposerWithoutSourceReferenceIsNotExact(): void
    {
        [$version, $exact] = $this->detectForPhpParserPackageData('5.6.x-dev', null);

        $this->assertSame('5.6.x-dev', $version);
        $this->assertFalse($exact);
    }

    public function testExtractsVersionOfPhpParserFromPharManifest(): void
    {
        $manifest = "phpunit/phpunit: 13.3.0\nnikic/php-parser: 5.6.1 (abc1234)\nsebastian/version: 7.0.0\n";

        $this->assertSame('5.6.1 (abc1234)', PhpParserVersion::versionFromManifest($manifest));
    }

    public function testDetectsThatPharManifestDoesNotContainVersionOfPhpParser(): void
    {
        $this->assertNull(PhpParserVersion::versionFromManifest("phpunit/phpunit: 13.3.0\n"));
    }

    /**
     * Runs the version detection with fresh state and returns the detected
     * version identifier and whether it is exact. The previously detected
     * (and cached) version is restored afterwards.
     *
     * @return array{0: non-empty-string, 1: bool}
     */
    private function detect(): array
    {
        $class = new ReflectionClass(PhpParserVersion::class);

        $version = $class->getStaticPropertyValue('version');
        $exact   = $class->getStaticPropertyValue('exact');

        $class->setStaticPropertyValue('version', null);
        $class->setStaticPropertyValue('exact', false);

        try {
            return [PhpParserVersion::id(), PhpParserVersion::isExact()];
        } finally {
            $class->setStaticPropertyValue('version', $version);
            $class->setStaticPropertyValue('exact', $exact);
        }
    }

    /**
     * Runs the version detection while Composer's runtime API reports the
     * given version and source reference for nikic/php-parser. The data
     * Composer's runtime API works on is restored afterwards.
     *
     * @return array{0: non-empty-string, 1: bool}
     */
    private function detectForPhpParserPackageData(string $prettyVersion, ?string $reference): array
    {
        $data = [
            'root' => [
                'name'           => 'phpunit/php-code-coverage',
                'pretty_version' => 'dev-main',
                'version'        => 'dev-main',
                'reference'      => null,
                'type'           => 'library',
                'install_path'   => __DIR__,
                'aliases'        => [],
                'dev'            => true,
            ],
            'versions' => [
                'nikic/php-parser' => [
                    'pretty_version'  => $prettyVersion,
                    'version'         => $prettyVersion,
                    'reference'       => $reference,
                    'type'            => 'library',
                    'install_path'    => __DIR__,
                    'aliases'         => [],
                    'dev_requirement' => false,
                ],
            ],
        ];

        // Composer's runtime API prefers the data loaded from the installed.php
        // files of all registered class loaders over data injected with reload(),
        // so the injected data has to be seeded for each vendor directory as well
        $installedByVendor = [];

        foreach (ClassLoader::getRegisteredLoaders() as $vendorDirectory => $loader) {
            $installedByVendor[strtr($vendorDirectory, '\\', '/')] = $data;
        }

        $class = new ReflectionClass(InstalledVersions::class);

        $originalInstalled           = $class->getStaticPropertyValue('installed');
        $originalInstalledByVendor   = $class->getStaticPropertyValue('installedByVendor');
        $originalInstalledIsLocalDir = $class->getStaticPropertyValue('installedIsLocalDir');

        $class->setStaticPropertyValue('installed', $data);
        $class->setStaticPropertyValue('installedByVendor', $installedByVendor);
        $class->setStaticPropertyValue('installedIsLocalDir', false);

        try {
            return $this->detect();
        } finally {
            $class->setStaticPropertyValue('installed', $originalInstalled);
            $class->setStaticPropertyValue('installedByVendor', $originalInstalledByVendor);
            $class->setStaticPropertyValue('installedIsLocalDir', $originalInstalledIsLocalDir);
        }
    }
}
