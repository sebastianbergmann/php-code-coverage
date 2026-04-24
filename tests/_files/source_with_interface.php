<?php declare(strict_types=1);
namespace SebastianBergmann\CodeCoverage\TestFixture;

interface InterfaceWithMethods
{
    public function singleLineMethod(): void;

    public function multiLineMethod(
        int $x,
        string $y
    ): void;

    public function anotherMultiLineMethod(
        \stdClass $obj
    ): \stdClass;
}
