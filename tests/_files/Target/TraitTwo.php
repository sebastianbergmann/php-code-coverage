<?php declare(strict_types=1);
namespace SebastianBergmann\CodeCoverage\TestFixture\Target;

trait TraitTwo
{
    use TraitOne;

    public function two(): void
    {
    }
}
