<?php
include_once('CoveredClass.php');
class CoverageClassExtendedTest extends PHPUnit_Framework_TestCase
{
    /**
     * @covers CoveredClass<extended>
     */
    public function testSomething()
    {
        $o = new CoveredClass;
        $o->publicMethod();
    }
}
