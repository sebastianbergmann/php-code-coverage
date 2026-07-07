<?php declare(strict_types=1);
namespace SebastianBergmann\CodeCoverage\TestFixture;

final class ClassWithDeadCode
{
    public function deadAfterReturn(): int
    {
        return 1;
        $x = 2;
        return $x;
    }

    public function deadAfterThrow(): void
    {
        throw new \RuntimeException;
        $x = 1;
    }

    public function deadAfterThrowExpression(): void
    {
        throw new \RuntimeException;
        $x = 1;
    }

    public function deadAfterExit(): void
    {
        exit(1);
        $x = 1;
    }

    public function deadAfterBreak(int $n): void
    {
        for ($i = 0; $i < $n; $i++) {
            break;
            $x = 1;
        }
    }

    public function deadAfterContinue(int $n): void
    {
        for ($i = 0; $i < $n; $i++) {
            continue;
            $x = 1;
        }
    }

    public function deadIfFalse(): void
    {
        if (false) {
            $x = 1;
            $y = 2;
        }
    }

    public function deadElseAfterTrue(): int
    {
        if (true) {
            return 1;
        } else {
            return 2;
        }
    }

    public function deadElseifAfterTrue(int $n): int
    {
        if (true) {
            return 1;
        } elseif ($n > 0) {
            return 2;
        }

        return 3;
    }

    public function deadElseifFalse(int $n): int
    {
        if ($n > 0) {
            return $n;
        } elseif (false) {
            return -$n;
        }

        return 0;
    }

    public function deadWhileFalse(): void
    {
        while (false) {
            $x = 1;
        }
    }

    public function deadForFalse(): void
    {
        for ($i = 0; false; $i++) {
            $x = 1;
        }
    }

    public function deadTernaryArm(int $n): int
    {
        return false
            ? $n
            : $n + 1;
    }

    public function liveCode(int $n): int
    {
        if ($n > 0) {
            return $n;
        }

        return -$n;
    }

    public function deadTernaryArmAfterTrue(int $n): int
    {
        return true
            ? $n
            : $n + 1;
    }

    public function singleLineTernary(int $n): int
    {
        return $n > 0 ? $n : -$n;
    }

    public function deadIfFalseSingleLine(): void
    {
        if (false) { $x = 1; }
    }

    public function deadIfFalseWithNop(): void
    {
        if (false) {
            $x = 1;
            /** trailing docblock parses to a Nop and must be skipped */
        }
    }

    public function codeAfterLabelIsReachable(bool $x): int
    {
        if ($x) {
            goto retry;
        }

        return 1;

        retry:

        return 2;
    }

    public function deadAfterGoto(): int
    {
        goto end;

        $x = 1;

        end:

        return 2;
    }

    public function labelledBlockInIfFalseIsReachable(bool $x): int
    {
        if ($x) {
            goto inside;
        }

        if (false) {
            inside:

            return 1;
        }

        return 2;
    }

    public function labelledElseAfterIfTrueIsReachable(bool $x): int
    {
        if ($x) {
            goto here;
        }

        if (true) {
            return 1;
        } else {
            here:

            return 2;
        }
    }
}
