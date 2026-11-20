<?php declare(strict_types=1);
/*
 * This file is part of phpunit/php-code-coverage.
 *
 * (c) Sebastian Bergmann <sebastian@phpunit.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace SebastianBergmann\CodeCoverage;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Small;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

#[CoversClass(Version::class)]
#[Small]
final class VersionTest extends TestCase
{
    public function testHasVersionIdThatStartsWithMajorAndMinorVersion(): void
    {
        // The version id is determined once per process, usually before this test is run
        (new ReflectionClass(Version::class))->setStaticPropertyValue('version', '');

        $this->assertMatchesRegularExpression('/^\d+\.\d+/', Version::id());
    }

    public function testVersionIdIsDeterminedOnlyOnce(): void
    {
        $version = new ReflectionClass(Version::class);

        $version->setStaticPropertyValue('version', '');

        $id = Version::id();

        $this->assertSame($id, $version->getStaticPropertyValue('version'));
        $this->assertSame($id, Version::id());
    }
}
