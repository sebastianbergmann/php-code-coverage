<?php
use PHPUnit\Framework\TestCase;

class NamespaceCovfefeMethodTest extends TestCase
{
    /**
     * @covers Foo\CoveredClass::publicMethod
     */
    public function testSomething()
    {
        $o = new Foo\CoveredClass;
        $o->publicMethod();
    }
}
