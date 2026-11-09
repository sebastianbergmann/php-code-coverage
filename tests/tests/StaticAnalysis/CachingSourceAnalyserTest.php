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
use function iterator_to_array;
use function sys_get_temp_dir;
use function uniqid;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Small;
use PHPUnit\Framework\Attributes\UsesClass;
use SebastianBergmann\CodeCoverage\Util\PhpParserVersion;

#[CoversClass(CachingSourceAnalyser::class)]
#[UsesClass(ParsingSourceAnalyser::class)]
#[UsesClass(CodeUnitFindingVisitor::class)]
#[UsesClass(IgnoredLinesFindingVisitor::class)]
#[Group('static-analysis')]
#[Small]
final class CachingSourceAnalyserTest extends SourceAnalyserTestCase
{
    public function testCachesAnalysisResultForFileThatCannotBeParsedWhenVersionOfPhpParserIsExactlyKnown(): void
    {
        if (!PhpParserVersion::isExact()) {
            $this->markTestSkipped('The version of nikic/php-parser is not exactly known');
        }

        $file   = TEST_FILES_PATH . 'source_that_cannot_be_parsed.php';
        $source = file_get_contents($file);

        if ($source === false) {
            $this->fail('Could not read ' . $file);
        }

        $analyser = new CachingSourceAnalyser(
            sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'php-code-coverage-caching-test-' . uniqid(),
            new ParsingSourceAnalyser,
        );

        $firstResult  = $analyser->analyse($file, $source, true, true);
        $secondResult = $analyser->analyse($file, $source, true, true);

        $this->assertSame(1, $analyser->cacheHits());
        $this->assertSame(1, $analyser->cacheMisses());

        $this->assertFalse($firstResult->wasParsed());
        $this->assertFalse($secondResult->wasParsed());
        $this->assertSame($firstResult->parseError(), $secondResult->parseError());
    }

    public function testCachesResultsOfAnalysersWithDifferentCacheKeyComponentsSeparately(): void
    {
        $directory = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'php-code-coverage-caching-test-' . uniqid();
        $file      = TEST_FILES_PATH . 'source_without_ignore.php';
        $source    = $this->source($file);

        $first  = new CachingSourceAnalyser($directory, new ParsingSourceAnalyser);
        $second = new CachingSourceAnalyser($directory, new ParsingSourceAnalyser, 'second');

        $first->analyse($file, $source, true, true);
        $second->analyse($file, $source, true, true);
        $second->analyse($file, $source, true, true);

        $this->assertSame(1, $second->cacheMisses());
        $this->assertSame(1, $second->cacheHits());
    }

    public function testYieldsFilesWhoseAnalysisIsNotCached(): void
    {
        $analyser = new CachingSourceAnalyser(
            sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'php-code-coverage-caching-test-' . uniqid(),
            new ParsingSourceAnalyser,
        );
        $cached    = TEST_FILES_PATH . 'source_without_ignore.php';
        $notCached = TEST_FILES_PATH . 'source_with_ignore.php';
        $missing   = TEST_FILES_PATH . 'does_not_exist.php';

        $analyser->analyse($cached, $this->source($cached), true, true);

        $this->assertSame(
            [$notCached, $missing],
            iterator_to_array($analyser->uncachedFiles([$cached, $notCached, $missing], true, true), false),
        );

        $this->assertSame(
            [$cached, $notCached, $missing],
            iterator_to_array($analyser->uncachedFiles([$cached, $notCached, $missing], false, true), false),
        );
    }

    protected function analyser(): SourceAnalyser
    {
        return $this->cachingAnalyser();
    }

    /**
     * @param non-empty-string $file
     */
    private function source(string $file): string
    {
        $source = file_get_contents($file);

        if ($source === false) {
            $this->fail('Could not read ' . $file);
        }

        return $source;
    }

    private function cachingAnalyser(): CachingSourceAnalyser
    {
        return new CachingSourceAnalyser(
            sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'php-code-coverage-tests-for-static-analysis',
            new ParsingSourceAnalyser,
        );
    }
}
