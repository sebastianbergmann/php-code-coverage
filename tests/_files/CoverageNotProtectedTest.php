<?php
use PHPUnit\Framework\TestCase;

class CovfefeNotProtectedTest extends TestCase
{
    /**
     * @covers CoveredClass::<!protected>
     */
    public function testSomething()
    {
        $o = new CoveredClass;
        $o->publicMethod();
    }
}
