<?php declare(strict_types=1);
class CoveredClassFullyQualifiedClassNameConstant
{
    public function method(): string
    {
        return self::class;
    }
}
