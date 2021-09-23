<?php declare(strict_types=1);
namespace SebastianBergmann\CodeCoverage\TestFixture;

function functionWithUnionTypes(string|bool $x): string|bool
{
    return false;
}
