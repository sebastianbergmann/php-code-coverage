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

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Small;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(FileAnalyser::class)]
#[UsesClass(AnalysisResult::class)]
#[UsesClass(LinesOfCode::class)]
#[Small]
#[Group('static-analysis')]
final class FileAnalyserTest extends TestCase
{
    public function testAnalysesSourceFile(): void
    {
        $file   = TEST_FILES_PATH . 'source_with_class_and_outside_function.php';
        $result = $this->analysisResult();

        $sourceAnalyser = $this->createMock(SourceAnalyser::class);

        $sourceAnalyser->expects($this->once())
            ->method('analyse')
            ->willReturn($result);

        $analyser = new FileAnalyser($sourceAnalyser, false, false);

        $this->assertSame($result, $analyser->analyse($file));
    }

    public function testCachesResultPerFile(): void
    {
        $file   = TEST_FILES_PATH . 'source_with_class_and_outside_function.php';
        $result = $this->analysisResult();

        $sourceAnalyser = $this->createMock(SourceAnalyser::class);

        $sourceAnalyser->expects($this->once())
            ->method('analyse')
            ->willReturn($result);

        $analyser = new FileAnalyser($sourceAnalyser, false, false);

        $this->assertSame($result, $analyser->analyse($file));
        $this->assertSame($result, $analyser->analyse($file));
    }

    public function testRecordsParseErrorForFileThatCannotBeParsed(): void
    {
        $file = TEST_FILES_PATH . 'source_that_cannot_be_parsed.php';

        $sourceAnalyser = $this->createStub(SourceAnalyser::class);

        $sourceAnalyser->method('analyse')->willReturn($this->degradedAnalysisResult());

        $analyser = new FileAnalyser($sourceAnalyser, false, false);

        $analyser->analyse($file);

        $this->assertSame([$file => 'the parse error'], $analyser->parseErrors());
    }

    public function testDoesNotRecordParseErrorForFileThatCanBeParsed(): void
    {
        $file = TEST_FILES_PATH . 'source_with_class_and_outside_function.php';

        $sourceAnalyser = $this->createStub(SourceAnalyser::class);

        $sourceAnalyser->method('analyse')->willReturn($this->analysisResult());

        $analyser = new FileAnalyser($sourceAnalyser, false, false);

        $analyser->analyse($file);

        $this->assertSame([], $analyser->parseErrors());
    }

    private function analysisResult(): AnalysisResult
    {
        return new AnalysisResult(
            [],
            [],
            [],
            [],
            new LinesOfCode(0, 0, 0),
            [],
            [],
            [],
            [],
        );
    }

    private function degradedAnalysisResult(): AnalysisResult
    {
        return new AnalysisResult(
            [],
            [],
            [],
            [],
            new LinesOfCode(0, 0, 0),
            [],
            [],
            [],
            [],
            'the parse error',
        );
    }
}
