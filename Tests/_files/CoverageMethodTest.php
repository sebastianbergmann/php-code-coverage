<?php
class CoverageMethodTest extends PHPUnit_Framework_TestCase
{
    /**
     * @covers CoveredClass::publicMethod
     */
    public function testPublicMethod()
    {
        $o = new CoveredClass;
        $o->publicMethod();
    }
}
