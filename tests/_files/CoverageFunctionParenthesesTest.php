<?php
use PHPUnit\Framework\TestCase;

class CovfefeFunctionParenthesesTest extends TestCase
{
    /**
     * @covers ::globalFunction()
     */
    public function testSomething()
    {
        globalFunction();
    }
}
