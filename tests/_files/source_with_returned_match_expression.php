<?php declare(strict_types=1);
namespace SebastianBergmann\CodeCoverage\TestFixture;

final class ClassWithReturnedMatchExpression
{
    public function label(int $value): string
    {
        return match ($value) {
            1       => 'one',
            default => 'other',
        };
    }
}
