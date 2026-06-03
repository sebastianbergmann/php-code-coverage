<?php declare(strict_types=1);
/*
 * This file is part of phpunit/php-code-coverage.
 *
 * (c) Sebastian Bergmann <sebastian@phpunit.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace SebastianBergmann\CodeCoverage\StaticAnalysis;

use const DIRECTORY_SEPARATOR;
use function sys_get_temp_dir;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Small;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

#[CoversClass(Registry::class)]
#[UsesClass(FileAnalyser::class)]
#[UsesClass(ParsingSourceAnalyser::class)]
#[UsesClass(CachingSourceAnalyser::class)]
#[Small]
#[Group('static-analysis')]
final class RegistryTest extends TestCase
{
    protected function setUp(): void
    {
        $this->resetRegistry();
    }

    protected function tearDown(): void
    {
        $this->resetRegistry();
    }

    public function testCreatesAnalyserWithoutCacheDirectory(): void
    {
        $this->assertInstanceOf(
            FileAnalyser::class,
            Registry::analyser(null, false, false),
        );
    }

    public function testCreatesAnalyserWithCacheDirectory(): void
    {
        $cacheDirectory = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'php-code-coverage-registry-test';

        $this->assertInstanceOf(
            FileAnalyser::class,
            Registry::analyser($cacheDirectory, false, false),
        );
    }

    public function testReturnsSameAnalyserOnSubsequentCalls(): void
    {
        $analyser = Registry::analyser(null, false, false);

        $this->assertSame($analyser, Registry::analyser(null, false, false));
    }

    private function resetRegistry(): void
    {
        (new ReflectionClass(Registry::class))->setStaticPropertyValue('analyser', null);
    }
}
