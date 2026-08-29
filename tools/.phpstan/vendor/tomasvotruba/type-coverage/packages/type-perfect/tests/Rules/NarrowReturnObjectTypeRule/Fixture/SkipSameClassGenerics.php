<?php

declare(strict_types=1);

namespace Rector\TypePerfect\Tests\Rules\NarrowReturnObjectTypeRule\Fixture;

use Iterator;

final class SkipSameClassGenerics
{
    /**
     * @return Iterator<int, string>
     */
    public function provide(): Iterator
    {
        return $this->createIterator();
    }

    /**
     * @return Iterator<int, string>
     */
    private function createIterator(): Iterator
    {
        yield 'value';
    }
}
