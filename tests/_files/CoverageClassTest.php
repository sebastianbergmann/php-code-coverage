<?php
use PHPUnit\Framework\TestCase;

class CovfefeClassTest extends TestCase
{
    /**
     * @covers CoveredClass
     */
    public function testSomething()
    {
        $o = new CoveredClass;
        $o->publicMethod();
    }
}
