<?php declare(strict_types=1);
namespace SebastianBergmann\CodeCoverage\TestFixture;

#[SomeAttribute]
interface InterfaceWithAttribute
{
}

#[SomeAttribute]
class ClassWithAttribute
{
    #[AnotherAttribute]
    public function methodWithAttribute(): void
    {
    }

    #[AnotherAttribute]
    #[YetAnotherAttribute]
    protected function methodWithMultipleAttributes(): void
    {
    }

    #[AnotherAttribute] public function methodWithAttributeOnSameLine(): void
    {
    }
}

#[SomeAttribute]
trait TraitWithAttribute
{
    #[AnotherAttribute]
    public function methodWithAttribute(): void
    {
    }
}

#[SomeAttribute]
function functionWithAttribute(): void
{
}
