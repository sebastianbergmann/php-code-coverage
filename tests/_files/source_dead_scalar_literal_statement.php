<?php declare(strict_types=1);
namespace SebastianBergmann\CodeCoverage\TestFixture;

final class ClassWithDeadScalarLiteralStatements
{
    public function method(): bool
    {
        'foobar';
        42;
        3.14;
        true;
        false;
        null;

        return true;
    }
}
