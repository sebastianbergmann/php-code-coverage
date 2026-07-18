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
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Small;
use PHPUnit\Framework\Attributes\UsesClass;

#[CoversClass(CachingSourceAnalyser::class)]
#[UsesClass(ParsingSourceAnalyser::class)]
#[UsesClass(CodeUnitFindingVisitor::class)]
#[UsesClass(IgnoredLinesFindingVisitor::class)]
#[Group('static-analysis')]
#[Small]
final class CachingSourceAnalyserTest extends SourceAnalyserTestCase
{
    public function testDoesNotCacheAnalysisResultForFileThatCannotBeParsed(): void
    {
        $file   = TEST_FILES_PATH . 'source_that_cannot_be_parsed.php';
        $source = file_get_contents($file);

        if ($source === false) {
            $this->fail('Could not read ' . $file);
        }

        $analyser = $this->cachingAnalyser();

        $analyser->analyse($file, $source, true, true);
        $analyser->analyse($file, $source, true, true);

        $this->assertSame(0, $analyser->cacheHits());
        $this->assertSame(2, $analyser->cacheMisses());
    }

    protected function analyser(): SourceAnalyser
    {
        return $this->cachingAnalyser();
    }

    private function cachingAnalyser(): CachingSourceAnalyser
    {
        return new CachingSourceAnalyser(
            sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'php-code-coverage-tests-for-static-analysis',
            new ParsingSourceAnalyser,
        );
    }
}
