<?php declare(strict_types=1);
namespace SebastianBergmann\CodeCoverage\TestFixture;

interface IntersectionPartOne
{
}

interface IntersectionPartTwo
{
}

function functionWithIntersectionTypes(IntersectionPartOne&IntersectionPartTwo $x): IntersectionPartOne&IntersectionPartTwo
{
    return false;
}
