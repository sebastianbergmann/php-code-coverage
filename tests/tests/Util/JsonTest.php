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

use const JSON_PRETTY_PRINT;
use const JSON_UNESCAPED_SLASHES;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Small;
use PHPUnit\Framework\TestCase;
use SebastianBergmann\CodeCoverage\JsonException;

#[CoversClass(Json::class)]
#[Small]
final class JsonTest extends TestCase
{
    public function testEncodesData(): void
    {
        $this->assertSame('{"file":"src\/Money.php"}', Json::encode(['file' => 'src/Money.php']));
    }

    public function testPassesFlagsOnToJsonEncode(): void
    {
        $this->assertSame(
            '{"file":"src/Money.php"}',
            Json::encode(['file' => 'src/Money.php'], JSON_UNESCAPED_SLASHES),
        );

        $this->assertSame(
            "{\n    \"executable\": 1\n}",
            Json::encode(['executable' => 1], JSON_PRETTY_PRINT),
        );
    }

    public function testThrowsExceptionOfThisPackageWhenDataCannotBeEncoded(): void
    {
        $this->expectException(JsonException::class);
        $this->expectExceptionMessage('Unable to generate the JSON: Malformed UTF-8 characters, possibly incorrectly encoded');

        Json::encode(['file' => "\xB1\x31"]);
    }
}
