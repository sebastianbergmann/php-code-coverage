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
use function copy;
use function sys_get_temp_dir;
use function uniqid;
use function unlink;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Medium;
use PHPUnit\Framework\Attributes\RequiresPhp;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

#[CoversClass(BytecodeLines::class)]
#[UsesClass(BytecodeDumpParser::class)]
#[Medium]
#[Group('static-analysis')]
final class BytecodeLinesTest extends TestCase
{
    private const string FILE = TEST_FILES_PATH . 'source_for_bytecode_analysis.php';

    /**
     * @var list<non-empty-string>
     */
    private array $temporaryFiles = [];

    protected function setUp(): void
    {
        // Whether bytecode can be analysed is determined once per process,
        // usually by a test that is run before this one
        (new ReflectionClass(BytecodeLines::class))->setStaticPropertyValue('available', null);
    }

    protected function tearDown(): void
    {
        foreach ($this->temporaryFiles as $file) {
            @unlink($file);
        }
    }

    #[RequiresPhp('>= 8.6.0-dev')]
    public function testIsAvailableAsOfPhp86(): void
    {
        $this->assertTrue(BytecodeLines::isAvailable());
    }

    #[RequiresPhp('< 8.6.0-dev')]
    public function testIsNotAvailableBeforePhp86(): void
    {
        $this->assertFalse(BytecodeLines::isAvailable());
    }

    #[RequiresPhp('>= 8.6.0-dev')]
    public function testDeterminesExecutableLinesOfFile(): void
    {
        $this->assertSame(
            [18, 24, 26, 31, 33, 37, 41],
            array_keys((new BytecodeLines)->executableLines(self::FILE) ?? []),
        );
    }

    #[RequiresPhp('>= 8.6.0-dev')]
    public function testDeterminesExecutableLinesOfFileWhosePathIsNotCanonical(): void
    {
        $file = TEST_FILES_PATH . '..' . DIRECTORY_SEPARATOR . '_files' . DIRECTORY_SEPARATOR . 'source_for_bytecode_analysis.php';

        $this->assertSame(
            [18, 24, 26, 31, 33, 37, 41],
            array_keys((new BytecodeLines)->executableLines($file) ?? []),
        );
    }

    #[RequiresPhp('>= 8.6.0-dev')]
    public function testCompilesExpectedFilesTogetherWithFirstFileThatIsAnalysed(): void
    {
        $expected = $this->temporaryCopyOf(self::FILE);

        $bytecodeLines = new BytecodeLines;
        $bytecodeLines->expect([$expected]);

        $this->assertNotNull($bytecodeLines->executableLines(self::FILE));

        unlink($expected);

        $this->assertSame(
            [18, 24, 26, 31, 33, 37, 41],
            array_keys($bytecodeLines->executableLines($expected) ?? []),
        );
    }

    #[RequiresPhp('>= 8.6.0-dev')]
    public function testCompilesExpectedFilesThatFollowFileThatCannotBeCompiled(): void
    {
        $cannotBeCompiled = TEST_FILES_PATH . 'source_that_cannot_be_parsed.php';
        $expected         = $this->temporaryCopyOf(self::FILE);

        $bytecodeLines = new BytecodeLines;
        $bytecodeLines->expect([$cannotBeCompiled, $expected]);

        $this->assertNotNull($bytecodeLines->executableLines(self::FILE));

        unlink($expected);

        $this->assertNull($bytecodeLines->executableLines($cannotBeCompiled));
        $this->assertSame(
            [18, 24, 26, 31, 33, 37, 41],
            array_keys($bytecodeLines->executableLines($expected) ?? []),
        );
    }

    #[RequiresPhp('>= 8.6.0-dev')]
    public function testReturnsNullForFileThatCannotBeCompiled(): void
    {
        $this->assertNull((new BytecodeLines)->executableLines(TEST_FILES_PATH . 'source_that_cannot_be_parsed.php'));
    }

    #[RequiresPhp('>= 8.6.0-dev')]
    public function testReturnsNullForFileThatDoesNotExist(): void
    {
        $this->assertNull((new BytecodeLines)->executableLines(TEST_FILES_PATH . 'does_not_exist.php'));
    }

    #[RequiresPhp('< 8.6.0-dev')]
    public function testReturnsNullForFilesWhenPhpCannotDumpBytecodeWithLineNumbers(): void
    {
        $bytecodeLines = new BytecodeLines;
        $bytecodeLines->expect([TEST_FILES_PATH . 'source_without_ignore.php']);

        $this->assertNull($bytecodeLines->executableLines(self::FILE));
        $this->assertNull($bytecodeLines->executableLines(TEST_FILES_PATH . 'source_without_ignore.php'));
    }

    public function testReturnsNullWhenPhpCannotBeStarted(): void
    {
        $this->assertNull((new BytecodeLines(TEST_FILES_PATH . 'does_not_exist'))->executableLines(self::FILE));
    }

    /**
     * @param non-empty-string $file
     *
     * @return non-empty-string
     */
    private function temporaryCopyOf(string $file): string
    {
        $copy = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'php-code-coverage-bytecode-test-' . uniqid() . '.php';

        copy($file, $copy);

        $this->temporaryFiles[] = $copy;

        return $copy;
    }
}
