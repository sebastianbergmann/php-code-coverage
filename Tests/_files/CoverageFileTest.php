<?php
class CoverageFunctionTest extends PHPUnit_Framework_TestCase
{
    /**
     * @covers Tests/_files/CoveredFile.php
     */
    public function testSomething()
    {
        anotherGlobalFunction();
    }
}
