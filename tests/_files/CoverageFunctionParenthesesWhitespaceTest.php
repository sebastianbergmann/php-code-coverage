<?php
use PHPUnit\Framework\TestCase;

class CovfefeFunctionParenthesesWhitespaceTest extends TestCase
{
    /**
     * @covers ::globalFunction ( )
     */
    public function testSomething()
    {
        globalFunction();
    }
}
