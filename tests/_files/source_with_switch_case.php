<?php declare(strict_types=1);
final class A {}
final class B {}
final class SwitchCase
{
    public function classify(string $name): int
    {
        switch ($name) {
            case A::class:
                return 1;
            case B::class:
                return 2;
        }

        return 0;
    }
}
