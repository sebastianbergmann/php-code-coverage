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

use const PHP_VERSION;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Medium;
use PHPUnit\Framework\Attributes\RequiresPhp;
use PHPUnit\Framework\Attributes\TestDox;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(ExecutableLinesClassifier::class)]
#[UsesClass(BytecodeDumpParser::class)]
#[UsesClass(BytecodeLines::class)]
#[Medium]
#[Group('static-analysis')]
final class ExecutableLinesClassifierTest extends TestCase
{
    /**
     * @return array<non-empty-string, array{bool, bool, false|string, false|string}>
     */
    public static function compilationProvider(): array
    {
        return [
            'OPcache without optimizations and file update protection' => [true, true, '0', '0'],
            'optimization level given as hexadecimal number'           => [true, true, '0x0', '0'],
            'OPcache is not enabled'                                   => [false, false, '0', '0'],
            'OPcache optimizes'                                        => [false, true, '0x7FFEBFFF', '0'],
            'OPcache does not compile recently modified files'         => [false, true, '0', '2'],
            'OPcache is not available'                                 => [false, true, false, false],
        ];
    }

    #[DataProvider('compilationProvider')]
    public function testDeterminesWhetherCodeIsCompiledLikeForBytecodeAnalysis(bool $expected, bool $opcacheIsEnabled, false|string $optimizationLevel, false|string $fileUpdateProtection): void
    {
        $this->assertSame(
            $expected,
            ExecutableLinesClassifier::isCompiledLikeBytecodeAnalysis($opcacheIsEnabled, $optimizationLevel, $fileUpdateProtection),
        );
    }

    #[RequiresPhp('>= 8.6.0-dev')]
    #[TestDox('Selects bytecode for driver whose lines do not depend on compilation (PHP >= 8.6)')]
    public function testSelectsBytecodeForDriverWhoseLinesDoNotDependOnCompilation(): void
    {
        $this->assertSame(ExecutableLinesClassifier::Bytecode, ExecutableLinesClassifier::select(false));
    }

    #[RequiresPhp('< 8.6.0-dev')]
    #[TestDox('Selects syntax for driver whose lines do not depend on compilation (PHP < 8.6)')]
    public function testSelectsSyntaxForDriverWhoseLinesDoNotDependOnCompilationBeforePhp86(): void
    {
        $this->assertSame(ExecutableLinesClassifier::Syntax, ExecutableLinesClassifier::select(false));
    }

    public function testSelectsSyntaxForDriverWhoseLinesDependOnCompilationWhenCodeIsNotCompiledLikeForBytecodeAnalysis(): void
    {
        if (ExecutableLinesClassifier::compilationMatchesBytecodeAnalysis()) {
            $this->markTestSkipped('This test requires that code is not compiled by OPcache without optimizations');
        }

        $this->assertSame(ExecutableLinesClassifier::Syntax, ExecutableLinesClassifier::select(true));
    }

    public function testResultsOfSyntaxAnalysisAreCachedUnderTheKeysTheyHadBefore(): void
    {
        $this->assertSame('', ExecutableLinesClassifier::Syntax->cacheKey());
    }

    public function testResultsOfBytecodeAnalysisAreCachedPerVersionOfPhp(): void
    {
        $this->assertSame('bytecode:' . PHP_VERSION, ExecutableLinesClassifier::Bytecode->cacheKey());
    }
}
