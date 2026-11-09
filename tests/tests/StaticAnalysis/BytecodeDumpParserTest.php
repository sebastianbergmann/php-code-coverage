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

use function explode;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Small;
use PHPUnit\Framework\Attributes\TestDox;
use PHPUnit\Framework\TestCase;

#[CoversClass(BytecodeDumpParser::class)]
#[Small]
#[Group('static-analysis')]
final class BytecodeDumpParserTest extends TestCase
{
    /**
     * Recorded with PHP 8.6 (opcache.opt_debug_level=0x40010000) for this file:
     *
     *  1 <?php declare(strict_types=1);
     *  2 final class Example
     *  3 {
     *  4     public int $value {
     *  5         set {
     *  6         }
     *  7     }
     *  8
     *  9     public function empty(int $a): void
     * 10     {
     * 11     }
     * 12
     * 13     public function sum(
     * 14         int $a,
     * 15         int $b,
     * 16     ): int {
     * 17         $sum = $a + $b;
     * 18
     * 19         return $sum;
     * 20     }
     * 21
     * 22     public function oneLine(): int { return 1; }
     * 23
     * 24     public function generator(): Generator
     * 25     {
     * 26         yield 1;
     * 27     }
     * 28 }
     * 29
     */
    private const string DUMP = <<<'EOT'
$_main:
     ; (lines=2, args=0, vars=0, tmps=0)
     ; (before optimizer)
     ; /path/to/Example.php:1-29
L0002 0000 DECLARE_CLASS_DELAYED string("example") string("")
L0029 0001 RETURN int(1)

Example::empty:
     ; (lines=2, args=1, vars=1, tmps=0)
     ; (before optimizer)
     ; /path/to/Example.php:9-11
L0009 0000 CV0($a) = RECV 1
L0011 0001 RETURN null

Example::sum:
     ; (lines=8, args=2, vars=3, tmps=2)
     ; (before optimizer)
     ; /path/to/Example.php:13-20
L0014 0000 CV0($a) = RECV 1
L0015 0001 CV1($b) = RECV 2
L0017 0002 T3 = ADD CV0($a) CV1($b)
L0017 0003 ASSIGN CV2($sum) T3
L0019 0004 VERIFY_RETURN_TYPE CV2($sum)
L0019 0005 RETURN CV2($sum)
L0020 0006 VERIFY_RETURN_TYPE
L0020 0007 RETURN null
LIVE RANGES:
     3: 0002 - 0003 (tmp/var)

Example::oneLine:
     ; (lines=3, args=0, vars=0, tmps=0)
     ; (before optimizer)
     ; /path/to/Example.php:22-22
L0022 0000 RETURN int(1)
L0022 0001 VERIFY_RETURN_TYPE
L0022 0002 RETURN null

Example::generator:
     ; (lines=3, args=0, vars=0, tmps=1)
     ; (before optimizer)
     ; /path/to/Example.php:24-27
L0024 0000 GENERATOR_CREATE
L0026 0001 YIELD int(1)
L0027 0002 GENERATOR_RETURN null

Example::$value::set:
     ; (lines=2, args=1, vars=1, tmps=0)
     ; (before optimizer)
     ; /path/to/Example.php:5-6
L0004 0000 CV0($value) = RECV 1
L0006 0001 RETURN null
EOT;

    public function testDeterminesExecutableLinesOfFile(): void
    {
        $this->assertSame(
            [
                '/path/to/Example.php' => [
                    11 => true,
                    17 => true,
                    19 => true,
                    22 => true,
                    26 => true,
                ],
            ],
            $this->parse(self::DUMP),
        );
    }

    #[TestDox('Does not count lines that only hold opcodes that Xdebug or PCOV do not report (RECV, VERIFY_RETURN_TYPE)')]
    public function testDoesNotCountLinesWithOnlyIgnoredOpcodes(): void
    {
        $lines = $this->parse(self::DUMP)['/path/to/Example.php'] ?? [];

        $this->assertArrayNotHasKey(9, $lines);
        $this->assertArrayNotHasKey(14, $lines);
        $this->assertArrayNotHasKey(15, $lines);
    }

    public function testDoesNotCountImplicitReturn(): void
    {
        $lines = $this->parse(self::DUMP)['/path/to/Example.php'] ?? [];

        $this->assertArrayNotHasKey(20, $lines);
        $this->assertArrayNotHasKey(27, $lines);
        $this->assertArrayNotHasKey(29, $lines);
    }

    public function testCountsImplicitReturnOfFunctionWithEmptyBody(): void
    {
        $this->assertArrayHasKey(11, $this->parse(self::DUMP)['/path/to/Example.php'] ?? []);
    }

    public function testDoesNotCountImplicitReturnOfPropertyHookWithEmptyBody(): void
    {
        $this->assertArrayNotHasKey(6, $this->parse(self::DUMP)['/path/to/Example.php'] ?? []);
    }

    public function testCountsExplicitReturnOnLastLineOfFunction(): void
    {
        $this->assertArrayHasKey(22, $this->parse(self::DUMP)['/path/to/Example.php'] ?? []);
    }

    public function testDoesNotCountCreationOfGenerator(): void
    {
        $this->assertArrayNotHasKey(24, $this->parse(self::DUMP)['/path/to/Example.php'] ?? []);
    }

    public function testDoesNotCountDeclarationOfClass(): void
    {
        $this->assertArrayNotHasKey(2, $this->parse(self::DUMP)['/path/to/Example.php'] ?? []);
    }

    public function testDeterminesExecutableLinesOfEachFileInDump(): void
    {
        $dump = <<<'EOT'
$_main:
     ; (lines=3, args=0, vars=0, tmps=1)
     ; (before optimizer)
     ; /path/to/first.php:1-4
L0003 0000 ECHO string("first")
L0004 0001 RETURN int(1)

$_main:
     ; (lines=2, args=0, vars=0, tmps=0)
     ; (before optimizer)
     ; C:\path\to\second.php:1-2
L0002 0000 ECHO string("second")
L0002 0001 RETURN int(1)

$_main:
     ; (lines=1, args=0, vars=0, tmps=0)
     ; (before optimizer)
     ; /path/to/third.php:1-1
L0001 0000 RETURN int(1)
EOT;

        $this->assertSame(
            [
                '/path/to/first.php'    => [3 => true],
                'C:\path\to\second.php' => [2 => true],
                '/path/to/third.php'    => [],
            ],
            $this->parse($dump),
        );
    }

    public function testDoesNotCountCheckThatFunctionWhoseReturnTypeIsNeverDoesNotReturn(): void
    {
        $dump = <<<'EOT'
Example::fail:
     ; (lines=4, args=0, vars=0, tmps=1)
     ; (before optimizer)
     ; /path/to/Example.php:3-6
L0005 0000 T0 = NEW 0 string("Exception")
L0005 0001 DO_FCALL
L0005 0002 THROW T0
L0006 0003 VERIFY_NEVER_TYPE

Example::empty:
     ; (lines=1, args=0, vars=0, tmps=0)
     ; (before optimizer)
     ; /path/to/Example.php:8-10
L0010 0000 VERIFY_NEVER_TYPE
EOT;

        $this->assertSame(['/path/to/Example.php' => [5 => true, 10 => true]], $this->parse($dump));
    }

    #[TestDox('Rejects dump without line numbers (PHP < 8.6)')]
    public function testRejectsDumpWithoutLineNumbers(): void
    {
        $dump = <<<'EOT'
$_main:
     ; (lines=2, args=0, vars=0, tmps=0)
     ; (before optimizer)
     ; /path/to/file.php:1-3
0000 ECHO string("file")
0001 RETURN int(1)
EOT;

        $this->assertNull((new BytecodeDumpParser)->parse(explode("\n", $dump)));
    }

    public function testReturnsNothingForEmptyDump(): void
    {
        $this->assertSame([], $this->parse(''));
    }

    /**
     * @return array<non-empty-string, array<positive-int, true>>
     */
    private function parse(string $dump): array
    {
        $result = (new BytecodeDumpParser)->parse(explode("\n", $dump));

        $this->assertNotNull($result);

        return $result;
    }
}
