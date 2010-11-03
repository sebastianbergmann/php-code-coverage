<?php
class NotExistingCoveredElementTest extends PHPUnit_Framework_TestCase
{
    /**
     * @covers NotExistingClass
     */
    public function testOne()
    {
    }

    /**
     * @covers CoveredClass::notExistingMethod
     */
    public function testTwo()
    {
    }

    /**
     * @covers NotExistingClass::<public>
     */
    public function testThree()
    {
    }

    /**
     * @covers Tests/_files/FileNotFound.php
     */
    public function testFour()
    {
    }
}
