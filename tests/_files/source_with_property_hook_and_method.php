<?php declare(strict_types=1);
namespace SebastianBergmann\CodeCoverage\TestFixture;

final class ClassWithPropertyHookAndMethod
{
    public string $name = '' {
        set(string $value) {
            $this->name = trim($value);
        }
    }

    public function name(): string
    {
        return $this->name;
    }
}
