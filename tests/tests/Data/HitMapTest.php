<?php declare(strict_types=1);
/*
 * This file is part of phpunit/php-code-coverage.
 *
 * (c) Sebastian Bergmann <sebastian@phpunit.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace SebastianBergmann\CodeCoverage\Data;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Small;
use PHPUnit\Framework\TestCase;

#[CoversClass(HitMap::class)]
#[Small]
final class HitMapTest extends TestCase
{
    public function testMergesTheHitCountsOfTwoMapsIntoANewMap(): void
    {
        $hit           = [0 => 1, 1 => 3];
        $additionalHit = [1 => 2, 2 => 4];

        $this->assertSame([0 => 1, 1 => 3, 2 => 4], HitMap::merge($hit, $additionalHit));
        $this->assertSame([0 => 1, 1 => 3], $hit);
    }

    public function testMergesTheHitCountsOfAMapIntoAnotherMapInPlace(): void
    {
        $hit = [0 => 1, 1 => 2];

        HitMap::mergeInto($hit, [1 => 3, 2 => 4]);

        $this->assertSame([0 => 1, 1 => 3, 2 => 4], $hit);
    }
}
