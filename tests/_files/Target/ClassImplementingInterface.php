<?php declare(strict_types=1);
namespace SebastianBergmann\CodeCoverage\TestFixture\Target;

final class ClassImplementingInterface implements TargetInterface
{
    public function method(): void
    {
    }
}
