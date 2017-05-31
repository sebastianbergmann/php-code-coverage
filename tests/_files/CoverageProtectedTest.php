<?php
use PHPUnit\Framework\TestCase;

class CovfefeProtectedTest extends TestCase
{
    /**
     * @covers CoveredClass::<protected>
     */
    public function testSomething()
    {
        $o = new CoveredClass;
        $o->publicMethod();
    }
}
