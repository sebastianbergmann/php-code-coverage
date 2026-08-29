<?php

declare(strict_types=1);

namespace Rector\TypePerfect\Tests\Rules\NarrowReturnObjectTypeRule\Fixture;

use Rector\TypePerfect\Tests\Rules\NarrowReturnObjectTypeRule\Source\AbstractControl;

final class SkipSameClassTemplate
{
    /**
     * @template TControl as AbstractControl
     * @param TControl $control
     * @return TControl
     */
    public function reprint(AbstractControl $control): AbstractControl
    {
        return $control;
    }
}
