<?php
namespace bar\baz;

/**
 * Represents foo.
 */
class source_with_namespace
{
}

/**
 * @param mixed $bar
 */
function &foo($bar)
{
    $baz = function () {};
    $a   = TRUE ? TRUE : FALSE;
    $b = "{$a}";
    $c = "${b}";
}
