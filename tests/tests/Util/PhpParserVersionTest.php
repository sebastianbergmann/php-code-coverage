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

use Composer\InstalledVersions;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Small;
use PHPUnit\Framework\TestCase;

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

    public function testExtractsVersionOfPhpParserFromPharManifest(): void
    {
        $manifest = "phpunit/phpunit: 13.3.0\nnikic/php-parser: 5.6.1 (abc1234)\nsebastian/version: 7.0.0\n";

        $this->assertSame('5.6.1 (abc1234)', PhpParserVersion::versionFromManifest($manifest));
    }

    public function testDetectsThatPharManifestDoesNotContainVersionOfPhpParser(): void
    {
        $this->assertNull(PhpParserVersion::versionFromManifest("phpunit/phpunit: 13.3.0\n"));
    }
}
