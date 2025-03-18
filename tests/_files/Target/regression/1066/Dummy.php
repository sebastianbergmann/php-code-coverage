<?php declare(strict_types=1);
namespace SebastianBergmann\CodeCoverage\TestFixture\Target\Issue1066;

class Dummy extends BaseDummy
{
    public function method1()
    {
        return __FUNCTION__;
    }

    public function method2()
    {
        return __FUNCTION__;
    }
}
