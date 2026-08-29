<?php

declare(strict_types=1);

namespace Rector\TypePerfect\Tests\Rules\NarrowPublicClassMethodParamTypeRule\Source\SuffixClosure;

use Rector\TypePerfect\Tests\Rules\NarrowPublicClassMethodParamTypeRule\Fixture\SkipClosureSuffixClass;

final class CallWithClosureSuffix
{
    public function run(SkipClosureSuffixClass $skipClosureSuffixClass): void
    {
        $skipClosureSuffixClass->handle(new CustomClosure());
    }
}
