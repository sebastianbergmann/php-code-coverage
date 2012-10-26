<?php
namespace bar\baz;

/**
 * Represents foo_with_namespace.
 */
class foo_with_namespace
{
}

/**
 * @param mixed $bar
 */
function &foo_with_namespace($bar)
{
    $baz = function() {};
    $a   = TRUE ? TRUE : FALSE;
    $b = "{$a}";
    $c = "${b}";
}
