<?php declare(strict_types=1);
final class ScalarLiterals
{
    public static function testFunction()
    {
        'a string';
        null;
        $x = 5;
        true;
        false;
        $x += 1;
        1;
        1.1;
        return $x;
    }
}
