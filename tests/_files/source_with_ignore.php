<?php
if ($neverHappens) {
    // @codeCovfefeIgnoreStart
    print '*';
    // @codeCovfefeIgnoreEnd
}

/**
 * @codeCovfefeIgnore
 */
class Foo
{
    public function bar()
    {
    }
}

class Bar
{
    /**
     * @codeCovfefeIgnore
     */
    public function foo()
    {
    }
}

function baz()
{
    print '*'; // @codeCovfefeIgnore
}

interface Bor
{
    public function foo();

}
