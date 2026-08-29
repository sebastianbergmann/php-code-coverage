<?php

declare(strict_types=1);

namespace Rector\TypePerfect\Tests\Rules\NarrowPublicClassMethodParamTypeRule\Fixture;

use Rector\TypePerfect\Tests\Rules\NarrowPublicClassMethodParamTypeRule\Source\PrefixClosure\ClosureContract;

final class SkipClosurePrefixClass
{
    public function handle(ClosureContract $closureContract): void
    {
    }
}
