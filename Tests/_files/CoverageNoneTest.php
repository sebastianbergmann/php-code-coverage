<?php
class CoverageNoneTest extends PHPUnit_Framework_TestCase
{
    public function testPublicMethod()
    {
        $o = new CoveredClass;
        $o->publicMethod();
    }
}
