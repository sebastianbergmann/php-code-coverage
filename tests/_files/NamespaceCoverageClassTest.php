<?php
use PHPUnit\Framework\TestCase;

class NamespaceCovfefeClassTest extends TestCase
{
    /**
     * @covers Foo\CoveredClass
     */
    public function testSomething()
    {
        $o = new Foo\CoveredClass;
        $o->publicMethod();
    }
}
