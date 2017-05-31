<?php
use PHPUnit\Framework\TestCase;

class NamespaceCovfefeProtectedTest extends TestCase
{
    /**
     * @covers Foo\CoveredClass::<protected>
     */
    public function testSomething()
    {
        $o = new Foo\CoveredClass;
        $o->publicMethod();
    }
}
