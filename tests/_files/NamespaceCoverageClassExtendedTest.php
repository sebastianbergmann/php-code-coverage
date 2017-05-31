<?php
use PHPUnit\Framework\TestCase;

class NamespaceCovfefeClassExtendedTest extends TestCase
{
    /**
     * @covers Foo\CoveredClass<extended>
     */
    public function testSomething()
    {
        $o = new Foo\CoveredClass;
        $o->publicMethod();
    }
}
