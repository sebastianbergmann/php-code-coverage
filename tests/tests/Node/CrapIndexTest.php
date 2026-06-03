<?php declare(strict_types=1);
/*
 * This file is part of phpunit/php-code-coverage.
 *
 * (c) Sebastian Bergmann <sebastian@phpunit.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace SebastianBergmann\CodeCoverage\Node;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Small;
use PHPUnit\Framework\TestCase;

#[CoversClass(CrapIndex::class)]
#[Small]
final class CrapIndexTest extends TestCase
{
    public function testCalculatesCrapIndexForUncoveredCode(): void
    {
        $crapIndex = new CrapIndex(2, 0.0);

        $this->assertSame('6', $crapIndex->asString());
    }

    public function testCalculatesCrapIndexForFullyCoveredCode(): void
    {
        $crapIndex = new CrapIndex(3, 100.0);

        $this->assertSame('3', $crapIndex->asString());
    }

    public function testCalculatesCrapIndexForCodeCoveredAtLeastNinetyFivePercent(): void
    {
        $crapIndex = new CrapIndex(5, 95.0);

        $this->assertSame('5', $crapIndex->asString());
    }

    public function testCalculatesCrapIndexForPartiallyCoveredCode(): void
    {
        $crapIndex = new CrapIndex(4, 50.0);

        $this->assertSame('6.00', $crapIndex->asString());
    }
}
