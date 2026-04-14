<?php declare(strict_types=1);
namespace SebastianBergmann\CodeCoverage\TestFixture;

abstract class AbstractClassWithAbstractMethod
{
    abstract public function abstractMethod(): string;

    abstract protected function anotherAbstractMethod(int $value): int;

    public function concreteMethod(): string
    {
        return 'concrete';
    }
}
