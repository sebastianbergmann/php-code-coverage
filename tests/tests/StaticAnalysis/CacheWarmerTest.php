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
use function uniqid;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Small;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use SebastianBergmann\CodeCoverage\Filter;

#[CoversClass(CacheWarmer::class)]
#[UsesClass(CachingSourceAnalyser::class)]
#[UsesClass(ParsingSourceAnalyser::class)]
#[UsesClass(CodeUnitFindingVisitor::class)]
#[UsesClass(IgnoredLinesFindingVisitor::class)]
#[Small]
final class CacheWarmerTest extends TestCase
{
    public function testWarmsCacheForFilesInFilter(): void
    {
        $filter = new Filter;

        $filter->includeFile(TEST_FILES_PATH . 'source_without_ignore.php');

        $cacheDirectory = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'php-code-coverage-cache-warmer-test-' . uniqid();

        $cacheWarmer = new CacheWarmer;

        $result = $cacheWarmer->warmCache(
            $cacheDirectory,
            true,
            true,
            $filter,
        );

        $this->assertArrayHasKey('cacheHits', $result);
        $this->assertArrayHasKey('cacheMisses', $result);
        $this->assertSame(0, $result['cacheHits']);
        $this->assertSame(1, $result['cacheMisses']);
    }
}
