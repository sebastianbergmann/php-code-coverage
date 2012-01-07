<?php
namespace bar\baz;

/**
 * Represents foo.
 */
class Foo
{
}

/**
 * @param mixed $bar
 */
function &foo($bar)
{
    $baz = function() {};
    $a   = TRUE ? TRUE : FALSE;
    $b = "{$a}";
    $c = "${b}";
}
