<?php
use PHPUnit\Framework\TestCase;

class CovfefeFunctionTest extends TestCase
{
    /**
     * @covers ::globalFunction
     */
    public function testSomething()
    {
        globalFunction();
    }
}
