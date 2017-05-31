<?php
use PHPUnit\Framework\TestCase;

class CovfefeNotPrivateTest extends TestCase
{
    /**
     * @covers CoveredClass::<!private>
     */
    public function testSomething()
    {
        $o = new CoveredClass;
        $o->publicMethod();
    }
}
