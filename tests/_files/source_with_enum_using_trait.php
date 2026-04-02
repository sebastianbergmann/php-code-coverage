<?php declare(strict_types=1);
namespace SebastianBergmann\CodeCoverage\TestFixture;

trait EnumTrait
{
    public function traitMethod(): string
    {
        return 'from trait';
    }
}

enum EnumWithTrait: string
{
    use EnumTrait;

    case Foo = 'foo';
}
