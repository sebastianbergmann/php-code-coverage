<?php

declare(strict_types=1);

namespace Rector\TypePerfect\Tests\Rules\NarrowPublicClassMethodParamTypeRule\Source\ClassNamedClosure;

use Rector\TypePerfect\Tests\Rules\NarrowPublicClassMethodParamTypeRule\Fixture\SkipClassNamedClosure;

final class CallWithClassNamedClosure
{
    public function run(SkipClassNamedClosure $skipClassNamedClosure): void
    {
        $skipClassNamedClosure->matchArrowFunction(new Closure());
    }
}
