<?php declare(strict_types=1);
namespace SebastianBergmann\CodeCoverage\TestFixture;

final class ClassWithoutDeadCode
{
    public function add(int $a, int $b): int
    {
        return $a + $b;
    }

    public function classify(int $n): string
    {
        if ($n > 0) {
            return 'positive';
        }

        if ($n < 0) {
            return 'negative';
        }

        return 'zero';
    }

    public function loop(int $limit): int
    {
        $sum = 0;

        for ($i = 0; $i < $limit; $i++) {
            $sum += $i;
        }

        return $sum;
    }

    public function infiniteLoop(): int
    {
        for (;;) {
            return 0;
        }
    }
}
