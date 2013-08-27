<?php
include_once('CoveredFunction.php');
class CoverageFunctionParenthesesTest extends PHPUnit_Framework_TestCase
{
    /**
     * @covers ::globalFunction()
     */
    public function testSomething()
    {
        globalFunction();
    }
}
