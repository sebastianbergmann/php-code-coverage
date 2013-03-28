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
     * @covers NotExistingClass::anyMethod
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
     * @covers ::notExistingFunction
     */
    public function testFour()
    {
    }

    /**
     * @covers CoveredClass::notExistingMethod
     */
    public function testFive()
    {
    }
}
