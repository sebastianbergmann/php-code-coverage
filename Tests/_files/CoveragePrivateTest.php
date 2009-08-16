<?php
class CoveragePrivateTest extends PHPUnit_Framework_TestCase
{
    /**
     * @covers CoveredClass::<private>
     */
    public function testPublicMethod()
    {
        $o = new CoveredClass;
        $o->publicMethod();
    }
}
