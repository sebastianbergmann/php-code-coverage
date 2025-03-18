<?php declare(strict_types=1);
namespace SebastianBergmann\CodeCoverage\TestFixture\Target\Issue1066;

class DummyWithTrait extends BaseDummy
{
    use SomeTrait; // commented in Case 1

    public function method1()
    {
        return __FUNCTION__;
    }

    public function method2()
    {
        return __FUNCTION__;
    }
}
