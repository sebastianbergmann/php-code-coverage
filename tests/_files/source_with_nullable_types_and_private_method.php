<?php declare(strict_types=1);
namespace SebastianBergmann\CodeCoverage\TestFixture;

class ClassWithNullableTypesAndPrivateMethod
{
    private function privateMethod(?string $value): ?int
    {
        return null;
    }

    public function publicMethod(): void
    {
    }
}
