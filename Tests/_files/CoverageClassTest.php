<?php
class CoverageClassTest extends PHPUnit_Framework_TestCase
{
    /**
     * @covers CoveredClass
     */
    public function testPublicMethod()
    {
        $o = new CoveredClass;
        $o->publicMethod();
    }
}
