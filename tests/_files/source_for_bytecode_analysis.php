<?php declare(strict_types=1);
namespace SebastianBergmann\CodeCoverage\TestFixture;

use function max;
use Generator;

const ANSWER = 42;

final class ClassForBytecodeAnalysis
{
    public int $value {
        set {
        }
    }

    public function empty(int $a): void
    {
    }

    public function sum(
        int $a,
        int $b,
    ): int {
        $sum = $a + $b;

        return $sum;
    }

    public function max(int $a, int $b): int
    {
        return max(
            $a,
            $b,
        );
    }

    public function oneLine(): int { return ANSWER; }

    public function generator(): Generator
    {
        yield 1;
    }
}
