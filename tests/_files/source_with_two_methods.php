<?php declare(strict_types=1);
namespace SebastianBergmann\CodeCoverage\TestFixture;

final class ClassWithTwoMethods
{
    public function one(): string
    {
        return 'one';
    }

    public function two(): string
    {
        return 'two';
    }
}
