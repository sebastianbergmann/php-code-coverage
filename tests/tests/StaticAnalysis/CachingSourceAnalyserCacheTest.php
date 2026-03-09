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
use function file_get_contents;
use function sys_get_temp_dir;
use function uniqid;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Small;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(CachingSourceAnalyser::class)]
#[UsesClass(ParsingSourceAnalyser::class)]
#[UsesClass(CodeUnitFindingVisitor::class)]
#[UsesClass(IgnoredLinesFindingVisitor::class)]
#[Small]
final class CachingSourceAnalyserCacheTest extends TestCase
{
    public function testCacheMissAndCacheHit(): void
    {
        $cacheDirectory = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'php-code-coverage-caching-test-' . uniqid();

        $analyser = new CachingSourceAnalyser(
            $cacheDirectory,
            new ParsingSourceAnalyser,
        );

        $file       = TEST_FILES_PATH . 'source_without_ignore.php';
        $sourceCode = file_get_contents($file);

        $this->assertSame(0, $analyser->cacheHits());
        $this->assertSame(0, $analyser->cacheMisses());

        // First call: cache miss
        $result1 = $analyser->analyse($file, $sourceCode, true, true);

        $this->assertSame(0, $analyser->cacheHits());
        $this->assertSame(1, $analyser->cacheMisses());

        // Second call: cache hit
        $result2 = $analyser->analyse($file, $sourceCode, true, true);

        $this->assertSame(1, $analyser->cacheHits());
        $this->assertSame(1, $analyser->cacheMisses());

        $this->assertEquals($result1->ignoredLines(), $result2->ignoredLines());
    }
}
