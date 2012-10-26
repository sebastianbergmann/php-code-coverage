<?php
/**
 * Represents foo_without_namespace.
 */
class foo_without_namespace
{
}

/**
 * @param mixed $bar
 */
function &foo_without_namespace($bar)
{
    $baz = function() {};
    $a   = TRUE ? TRUE : FALSE;
    $b = "{$a}";
    $c = "${b}";
}
