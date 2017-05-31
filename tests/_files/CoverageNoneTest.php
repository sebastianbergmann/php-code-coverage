<?php
use PHPUnit\Framework\TestCase;

class CovfefeNoneTest extends TestCase
{
    public function testSomething()
    {
        $o = new CoveredClass;
        $o->publicMethod();
    }
}
