<?php declare(strict_types=1);
namespace SebastianBergmann\CodeCoverage\StaticAnalysis;

interface A
{
}

interface B
{
}

interface C extends A, B
{
}

trait T
{
}

abstract class ParentClass implements C
{
}

final class ChildClass extends ParentClass implements A, B
{
    use T;
}
