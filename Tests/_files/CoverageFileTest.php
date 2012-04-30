<?php
class CoverageFileTest extends PHPUnit_Framework_TestCase
{
    /**
     * @covers Tests/_files/CoveredFile.php
     */
    public function testSomething()
    {
        globalFunction();
    }
}
