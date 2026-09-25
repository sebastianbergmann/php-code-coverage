<?php declare(strict_types=1);
namespace SebastianBergmann\CodeCoverage\TestFixture;

abstract class ClassWithAbstractMethodWithConstantDefaultValues
{
    public const EQ = '=';

    abstract public function singleLine(string $operator = self::EQ): string;

    abstract public function multiLine(
        string $operator = self::EQ,
        string $other = self::EQ,
    ): string;

    public function concreteMethod(): string
    {
        return $this->singleLine();
    }
}
