<?php
use PHPUnit\Framework\TestCase;

class NamespaceCovfefeNotPublicTest extends TestCase
{
    /**
     * @covers Foo\CoveredClass::<!public>
     */
    public function testSomething()
    {
        $o = new Foo\CoveredClass;
        $o->publicMethod();
    }
}
