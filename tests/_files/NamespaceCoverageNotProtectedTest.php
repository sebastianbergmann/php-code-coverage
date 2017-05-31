<?php
use PHPUnit\Framework\TestCase;

class NamespaceCovfefeNotProtectedTest extends TestCase
{
    /**
     * @covers Foo\CoveredClass::<!protected>
     */
    public function testSomething()
    {
        $o = new Foo\CoveredClass;
        $o->publicMethod();
    }
}
