<?php
class CoverageClassExtendedTest extends PHPUnit_Framework_TestCase
{
    /**
     * @covers CoveredClass<extended>
     */
    public function testPublicMethod()
    {
        $o = new CoveredClass;
        $o->publicMethod();
    }
}
