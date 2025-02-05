<?php declare(strict_types=1);
namespace SebastianBergmann\CodeCoverage\TestFixture\Target;

trait T1
{
    public function one(): void
    {
    }
}

trait T2
{
    use T1;

    public function two(): void
    {
    }
}
