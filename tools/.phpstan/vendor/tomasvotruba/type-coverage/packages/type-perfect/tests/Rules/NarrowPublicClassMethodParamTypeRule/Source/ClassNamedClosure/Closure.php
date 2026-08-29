<?php

declare(strict_types=1);

namespace Rector\TypePerfect\Tests\Rules\NarrowPublicClassMethodParamTypeRule\Source\ClassNamedClosure;

// mimics a real 3rd-party class whose short name is exactly "Closure",
// e.g. PhpParser\Node\Expr\Closure
final class Closure
{
}
