<?php declare(strict_types=1);

namespace Example;

class ChildClass extends ParentClass
{
    use ExampleTrait;

    public function childMethod(): void
    {
        echo 'child';
    }
}
