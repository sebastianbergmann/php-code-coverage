<?php declare(strict_types=1);
namespace SebastianBergmann\CodeCoverage\TestFixture;

class ClassWithDeprecatedMethod
{
    /**
     * @deprecated
     */
    public function deprecatedMethod(): void
    {
    }

    public function normalMethod(): void
    {
    }
}
