<?php declare(strict_types=1);
class ClassInFileWithOutsideFunction
{
   public static function classMethod(): string
   {
       return self::class;
   }
}

function outsideFunction(bool $test): int
{
    if ($test) {
        return 1;
    }

    return 0;
}
