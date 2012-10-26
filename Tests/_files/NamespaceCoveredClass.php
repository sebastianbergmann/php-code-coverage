<?php
namespace Foo;

class CoveredNamespaceParentClass
{
    private function privateMethod()
    {
    }

    protected function protectedMethod()
    {
        $this->privateMethod();
    }

    public function publicMethod()
    {
        $this->protectedMethod();
    }
}

class CoveredNamespaceClass extends CoveredNamespaceParentClass
{
    private function privateMethod()
    {
    }

    protected function protectedMethod()
    {
        parent::protectedMethod();
        $this->privateMethod();
    }

    public function publicMethod()
    {
        parent::publicMethod();
        $this->protectedMethod();
    }
}
