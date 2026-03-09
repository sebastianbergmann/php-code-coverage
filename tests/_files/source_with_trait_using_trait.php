<?php declare(strict_types=1);
namespace SebastianBergmann\CodeCoverage\TestFixture;

trait InnerTrait
{
    public function innerMethod(): void
    {
    }
}

trait OuterTrait
{
    use InnerTrait;

    public function outerMethod(): void
    {
    }
}
