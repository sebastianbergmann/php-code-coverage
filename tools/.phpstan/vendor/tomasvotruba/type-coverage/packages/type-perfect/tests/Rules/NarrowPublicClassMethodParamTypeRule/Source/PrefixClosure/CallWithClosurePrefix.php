<?php

declare(strict_types=1);

namespace Rector\TypePerfect\Tests\Rules\NarrowPublicClassMethodParamTypeRule\Source\PrefixClosure;

use Rector\TypePerfect\Tests\Rules\NarrowPublicClassMethodParamTypeRule\Fixture\SkipClosurePrefixClass;

final class CallWithClosurePrefix
{
    public function run(SkipClosurePrefixClass $skipClosurePrefixClass): void
    {
        $skipClosurePrefixClass->handle(new ClosureContract());
    }
}
