<?php declare(strict_types=1);
namespace SebastianBergmann\CodeCoverage\TestFixture;

abstract class ClassWithAbstractMethod
{
    abstract public function abstractMethod(): void;

    public function concreteMethod(): void
    {
    }
}
