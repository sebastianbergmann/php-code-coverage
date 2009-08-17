<?php
class NamespaceCoveragePublicTest extends PHPUnit_Framework_TestCase
{
    /**
     * @covers Foo\CoveredClass::<public>
     */
    public function testPublicMethod()
    {
        $o = new Foo\CoveredClass;
        $o->publicMethod();
    }
}
