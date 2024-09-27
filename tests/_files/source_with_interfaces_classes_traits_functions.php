<?php declare(strict_types=1);
namespace SebastianBergmann\CodeCoverage\StaticAnalysis;

interface A
{
    public function one(): void;
}

interface B
{
    public function two(): A;
}

interface C extends A, B
{
    public function three(): A&B;
}

trait T
{
    public function four(): void
    {
    }
}

abstract class ParentClass implements C
{
    public function five(A $a, B $b): void
    {
    }
}

final class ChildClass extends ParentClass implements A, B
{
    use T;

    public function six(A $a, B $b): C
    {
    }

    public function one(): void
    {
    }

    public function two(): A
    {
    }

    public function three(): A&B
    {
    }
}

function f()
{
}
