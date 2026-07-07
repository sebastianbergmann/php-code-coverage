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
use function assert;
use function chmod;
use function file_get_contents;
use function file_put_contents;
use function glob;
use function is_readable;
use function restore_error_handler;
use function serialize;
use function set_error_handler;
use function sys_get_temp_dir;
use function uniqid;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Small;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\Attributes\WithoutErrorHandler;
use PHPUnit\Framework\TestCase;
use stdClass;

#[CoversClass(CachingSourceAnalyser::class)]
#[UsesClass(ParsingSourceAnalyser::class)]
#[UsesClass(CodeUnitFindingVisitor::class)]
#[UsesClass(IgnoredLinesFindingVisitor::class)]
#[Group('static-analysis')]
#[Small]
final class CachingSourceAnalyserCacheTest extends TestCase
{
    /**
     * @var non-empty-string
     */
    private string $cacheDirectory;

    public function testCacheMissAndCacheHit(): void
    {
        $analyser = $this->analyser();

        $file       = TEST_FILES_PATH . 'source_without_ignore.php';
        $sourceCode = file_get_contents($file);

        $this->assertNotFalse($sourceCode);
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

    public function testAnalysesWithDifferentConfigurationDoNotShareCacheEntries(): void
    {
        $analyser = $this->analyser();

        $file       = TEST_FILES_PATH . 'source_without_ignore.php';
        $sourceCode = file_get_contents($file);

        $this->assertNotFalse($sourceCode);

        $analyser->analyse($file, $sourceCode, true, true);
        $analyser->analyse($file, $sourceCode, false, true);
        $analyser->analyse($file, $sourceCode, true, false);
        $analyser->analyse($file, $sourceCode, false, false);

        $this->assertSame(0, $analyser->cacheHits());
        $this->assertSame(4, $analyser->cacheMisses());
    }

    public function testCacheFileWithCorruptedContentIsTreatedAsCacheMiss(): void
    {
        $analyser = $this->analyser();

        $file       = TEST_FILES_PATH . 'source_without_ignore.php';
        $sourceCode = file_get_contents($file);

        $this->assertNotFalse($sourceCode);

        $result1 = $analyser->analyse($file, $sourceCode, true, true);

        file_put_contents($this->cacheFile(), serialize(null));

        $result2 = $analyser->analyse($file, $sourceCode, true, true);

        $this->assertSame(0, $analyser->cacheHits());
        $this->assertSame(2, $analyser->cacheMisses());

        $this->assertEquals($result1->ignoredLines(), $result2->ignoredLines());
    }

    public function testCacheFileWithObjectOfDisallowedClassIsTreatedAsCacheMiss(): void
    {
        $analyser = $this->analyser();

        $file       = TEST_FILES_PATH . 'source_without_ignore.php';
        $sourceCode = file_get_contents($file);

        $this->assertNotFalse($sourceCode);

        $analyser->analyse($file, $sourceCode, true, true);

        file_put_contents($this->cacheFile(), serialize(new stdClass));

        $analyser->analyse($file, $sourceCode, true, true);

        $this->assertSame(0, $analyser->cacheHits());
        $this->assertSame(2, $analyser->cacheMisses());
    }

    #[WithoutErrorHandler]
    public function testCacheFileThatCannotBeReadIsTreatedAsCacheMiss(): void
    {
        $analyser = $this->analyser();

        $file       = TEST_FILES_PATH . 'source_without_ignore.php';
        $sourceCode = file_get_contents($file);

        $this->assertNotFalse($sourceCode);

        $analyser->analyse($file, $sourceCode, true, true);

        $cacheFile = $this->cacheFile();

        chmod($cacheFile, 0);

        if (is_readable($cacheFile)) {
            $this->markTestSkipped('Cache file cannot be made unreadable');
        }

        set_error_handler(static fn (): bool => true);

        try {
            $analyser->analyse($file, $sourceCode, true, true);
        } finally {
            restore_error_handler();
        }

        $this->assertSame(0, $analyser->cacheHits());
        $this->assertSame(2, $analyser->cacheMisses());
    }

    private function analyser(): CachingSourceAnalyser
    {
        $this->cacheDirectory = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'php-code-coverage-caching-test-' . uniqid();

        return new CachingSourceAnalyser(
            $this->cacheDirectory,
            new ParsingSourceAnalyser,
        );
    }

    /**
     * @return non-empty-string
     */
    private function cacheFile(): string
    {
        $cacheFiles = glob($this->cacheDirectory . DIRECTORY_SEPARATOR . '*');

        $this->assertNotFalse($cacheFiles);
        $this->assertCount(1, $cacheFiles);

        $cacheFile = $cacheFiles[0];

        assert($cacheFile !== '');

        return $cacheFile;
    }
}
