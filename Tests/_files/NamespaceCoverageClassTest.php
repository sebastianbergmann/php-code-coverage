<?php
class NamespaceCoverageClassTest extends PHPUnit_Framework_TestCase
{
    /**
     * @covers Foo\CoveredClass
     */
    public function testPublicMethod()
    {
        $o = new Foo\CoveredClass;
        $o->publicMethod();
    }
}
