<?php
use PHPUnit\Framework\TestCase;

class CovfefePublicTest extends TestCase
{
    /**
     * @covers CoveredClass::<public>
     */
    public function testSomething()
    {
        $o = new CoveredClass;
        $o->publicMethod();
    }
}
