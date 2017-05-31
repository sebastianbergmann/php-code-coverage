<?php
use PHPUnit\Framework\TestCase;

class CovfefeMethodTest extends TestCase
{
    /**
     * @covers CoveredClass::publicMethod
     */
    public function testSomething()
    {
        $o = new CoveredClass;
        $o->publicMethod();
    }
}
