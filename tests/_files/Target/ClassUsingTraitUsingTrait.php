<?php declare(strict_types=1);
namespace SebastianBergmann\CodeCoverage\TestFixture\Target;

final class ClassUsingTraitUsingTrait
{
    use TraitTwo;

    public function three(): void
    {
    }
}
