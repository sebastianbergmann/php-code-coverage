<?php

declare(strict_types=1);

namespace Rector\TypePerfect\Tests\Rules\NarrowPublicClassMethodParamTypeRule\Fixture;

use Rector\TypePerfect\Tests\Rules\NarrowPublicClassMethodParamTypeRule\Source\SuffixClosure\CustomClosure;

final class SkipClosureSuffixClass
{
    public function handle(CustomClosure $customClosure): void
    {
    }
}
