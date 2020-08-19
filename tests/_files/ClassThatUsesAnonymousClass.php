<?php declare(strict_types=1);
namespace SebastianBergmann\CodeCoverage\TestFixture;

final class ClassThatUsesAnonymousClass
{
    public function method(): string
    {
        $o = new class {
            public function method(): string
            {
                return 'result';
            }
        };

        return $o->method();
    }
}
