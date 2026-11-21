<?php declare(strict_types=1);
namespace SebastianBergmann\CodeCoverage\TestFixture;

final class ClassWithMethodWithFunctionCall
{
    public function one(): string
    {
        return 'one';
    }

    public function two(string $value): string
    {
        $value = \trim($value);

        return $value;
    }
}
