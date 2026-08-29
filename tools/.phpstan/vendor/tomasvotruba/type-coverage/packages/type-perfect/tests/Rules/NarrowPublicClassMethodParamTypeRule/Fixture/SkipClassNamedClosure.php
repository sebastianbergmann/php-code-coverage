<?php

declare(strict_types=1);

namespace Rector\TypePerfect\Tests\Rules\NarrowPublicClassMethodParamTypeRule\Fixture;

use Rector\TypePerfect\Tests\Rules\NarrowPublicClassMethodParamTypeRule\Source\ClassNamedClosure\Closure;

final class SkipClassNamedClosure
{
    public function matchArrowFunction(Closure $closure): void
    {
    }
}
