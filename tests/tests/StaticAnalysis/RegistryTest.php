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
use function array_keys;
use function sys_get_temp_dir;
use function uniqid;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RequiresPhp;
use PHPUnit\Framework\Attributes\Small;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

#[CoversClass(Registry::class)]
#[UsesClass(FileAnalyser::class)]
#[UsesClass(ParsingSourceAnalyser::class)]
#[UsesClass(CachingSourceAnalyser::class)]
#[UsesClass(BytecodeSourceAnalyser::class)]
#[UsesClass(BytecodeLines::class)]
#[UsesClass(BytecodeDumpParser::class)]
#[UsesClass(ExecutableLinesClassifier::class)]
#[UsesClass(CodeUnitFindingVisitor::class)]
#[UsesClass(IgnoredLinesFindingVisitor::class)]
#[UsesClass(ExecutableLinesFindingVisitor::class)]
#[UsesClass(DeadCodeFindingVisitor::class)]
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

    public function testReturnsSameAnalyserOnSubsequentCallsForSameConfiguration(): void
    {
        $analyser = Registry::analyser(null, false, false);

        $this->assertSame($analyser, Registry::analyser(null, false, false));
    }

    public function testReturnsDifferentAnalyserForDifferentCacheDirectory(): void
    {
        $cacheDirectory = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'php-code-coverage-registry-test';

        $this->assertNotSame(
            Registry::analyser(null, false, false),
            Registry::analyser($cacheDirectory, false, false),
        );
    }

    public function testReturnsDifferentAnalyserWhenAnnotationsForIgnoringCodeAreUsed(): void
    {
        $this->assertNotSame(
            Registry::analyser(null, false, false),
            Registry::analyser(null, true, false),
        );
    }

    public function testReturnsDifferentAnalyserWhenDeprecatedCodeIsIgnored(): void
    {
        $this->assertNotSame(
            Registry::analyser(null, false, false),
            Registry::analyser(null, false, true),
        );
    }

    public function testReturnsDifferentAnalyserForDifferentWayOfDeterminingExecutableLines(): void
    {
        $this->assertNotSame(
            Registry::analyser(null, false, false, ExecutableLinesClassifier::Syntax),
            Registry::analyser(null, false, false, ExecutableLinesClassifier::Bytecode),
        );
    }

    public function testDeterminesExecutableLinesFromSyntaxByDefault(): void
    {
        $this->assertSame(
            Registry::analyser(null, false, false),
            Registry::analyser(null, false, false, ExecutableLinesClassifier::Syntax),
        );
    }

    #[RequiresPhp('>= 8.6.0')]
    public function testCreatesAnalyserThatDeterminesExecutableLinesFromBytecode(): void
    {
        $file = TEST_FILES_PATH . 'source_for_bytecode_analysis.php';

        $this->assertSame(
            [18, 24, 26, 31, 33, 37, 41],
            array_keys(
                Registry::analyser(null, false, false, ExecutableLinesClassifier::Bytecode, [$file])
                    ->analyse($file)
                    ->executableLines(),
            ),
        );
    }

    #[RequiresPhp('>= 8.6.0')]
    public function testCreatesCachingAnalyserThatDeterminesExecutableLinesFromBytecode(): void
    {
        $file           = TEST_FILES_PATH . 'source_for_bytecode_analysis.php';
        $cacheDirectory = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'php-code-coverage-registry-test-' . uniqid();

        $this->assertSame(
            [18, 24, 26, 31, 33, 37, 41],
            array_keys(
                Registry::analyser($cacheDirectory, false, false, ExecutableLinesClassifier::Bytecode, [$file])
                    ->analyse($file)
                    ->executableLines(),
            ),
        );
    }

    #[RequiresPhp('< 8.6.0')]
    public function testCreatesCachingAnalyserThatDeterminesExecutableLinesFromSyntaxWhenBytecodeCannotBeAnalysedBeforePhp86(): void
    {
        $file           = TEST_FILES_PATH . 'source_for_bytecode_analysis.php';
        $cacheDirectory = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'php-code-coverage-registry-test-' . uniqid();

        $this->assertSame(
            Registry::analyser(null, false, false)->analyse($file)->executableLines(),
            Registry::analyser($cacheDirectory, false, false, ExecutableLinesClassifier::Bytecode, [$file])
                ->analyse($file)
                ->executableLines(),
        );
    }

    private function resetRegistry(): void
    {
        $registry = new ReflectionClass(Registry::class);

        $registry->setStaticPropertyValue('analysers', []);
        $registry->setStaticPropertyValue('cachingSourceAnalysers', []);
        $registry->setStaticPropertyValue('bytecodeLines', null);
    }
}
