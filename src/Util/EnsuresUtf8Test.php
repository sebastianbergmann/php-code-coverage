<?php declare(strict_types=1);
/*
 * This file is part of phpunit/php-code-coverage.
 *
 * (c) Sebastian Bergmann <sebastian@phpunit.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace SebastianBergmann\CodeCoverage\Util;

use function mb_check_encoding;
use PHPUnit\Framework\Attributes\CoversTrait;
use PHPUnit\Framework\Attributes\Small;
use PHPUnit\Framework\TestCase;

#[CoversTrait(EnsuresUtf8::class)]
#[Small]
final class EnsuresUtf8Test extends TestCase
{
    use EnsuresUtf8;

    public function testUtf8StringIsReturnedUnchanged(): void
    {
        $this->assertSame('Hello, World!', $this->ensureUtf8('Hello, World!'));
    }

    public function testUtf8StringWithMultibyteCharactersIsReturnedUnchanged(): void
    {
        $value = 'Héllo Wörld — über';

        $this->assertSame($value, $this->ensureUtf8($value));
    }

    public function testIso88591StringIsConvertedToUtf8(): void
    {
        $iso88591 = "\x48\xe9\x6c\x6c\x6f"; // "Héllo" in ISO-8859-1
        $expected = "H\xc3\xa9llo";           // "Héllo" in UTF-8

        $this->assertSame($expected, $this->ensureUtf8($iso88591));
    }

    public function testWindows1252StringIsConvertedToUtf8(): void
    {
        $windows1252 = "\x93smart quotes\x94"; // "smart quotes" in Windows-1252
        $expected    = "\xe2\x80\x9csmart quotes\xe2\x80\x9d"; // same in UTF-8

        $this->assertSame($expected, $this->ensureUtf8($windows1252));
    }

    public function testEmptyStringIsReturnedUnchanged(): void
    {
        $this->assertSame('', $this->ensureUtf8(''));
    }

    public function testAsciiStringIsReturnedUnchanged(): void
    {
        $this->assertSame('plain ascii', $this->ensureUtf8('plain ascii'));
    }

    public function testStringWithInvalidSequenceFallsBackToIso88591Conversion(): void
    {
        $binary = "\x80\x81\x82\x83";

        $result = $this->ensureUtf8($binary);

        $this->assertTrue(
            mb_check_encoding($result, 'UTF-8'),
            'Result must be valid UTF-8',
        );
    }
}
