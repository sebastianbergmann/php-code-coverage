<?php
use PHPUnit\Framework\TestCase;

class CovfefeClassExtendedTest extends TestCase
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
