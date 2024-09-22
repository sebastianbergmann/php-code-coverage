<?php declare(strict_types=1);
/*
 * This file is part of phpunit/php-code-coverage.
 *
 * (c) Sebastian Bergmann <sebastian@phpunit.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace SebastianBergmann\CodeCoverage\Test\Target;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Small;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(TargetCollection::class)]
#[CoversClass(TargetCollectionIterator::class)]
#[UsesClass(Class_::class)]
#[Small]
final class TargetCollectionTest extends TestCase
{
    public function testCanBeEmpty(): void
    {
        $collection = TargetCollection::fromArray([]);

        $this->assertCount(0, $collection);
        $this->assertTrue($collection->isEmpty());
        $this->assertFalse($collection->isNotEmpty());
    }

    public function testCanBeCreatedFromArray(): void
    {
        $target     = Target::forClass('className');
        $collection = TargetCollection::fromArray([$target]);

        $this->assertContains($target, $collection);
    }

    public function testIsCountable(): void
    {
        $target     = Target::forClass('className');
        $collection = TargetCollection::fromArray([$target]);

        $this->assertCount(1, $collection);
        $this->assertFalse($collection->isEmpty());
        $this->assertTrue($collection->isNotEmpty());
    }

    public function testIsIterable(): void
    {
        $target     = Target::forClass('className');
        $collection = TargetCollection::fromArray([$target]);

        foreach ($collection as $key => $value) {
            $this->assertSame(0, $key);
            $this->assertSame($target, $value);
        }
    }
}
