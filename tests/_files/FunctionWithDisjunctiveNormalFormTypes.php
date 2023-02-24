<?php declare(strict_types=1);
namespace SebastianBergmann\CodeCoverage\TestFixture;

interface A
{
}

interface B
{
}

interface C
{
}

interface D
{
}

interface X
{
}

function f((A&B)|D $x): void
{
}

function g(C|(X&D)|null $x): void
{
}

function h((A&B&D)|int|null $x): void
{
}
