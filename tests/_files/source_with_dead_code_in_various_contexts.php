<?php declare(strict_types=1);
namespace SebastianBergmann\CodeCoverage\TestFixture\DeadCodeContexts;

function deadCodeInFunctionBody(): int
{
    return 1;
    $x = 2;
}

function deadCodeInClosureBody(): callable
{
    return function (): int {
        return 1;
        $x = 2;
    };
}

function deadCodeInIfBody(bool $b): int
{
    if ($b) {
        return 1;
        $x = 2;
    }

    return 0;
}

function deadCodeInElseBody(bool $b): int
{
    if ($b) {
        return 1;
    } else {
        return 2;
        $x = 3;
    }
}

function deadCodeInElseifBody(int $n): int
{
    if ($n > 0) {
        return 1;
    } elseif ($n < 0) {
        return 2;
        $x = 3;
    }

    return 0;
}

function deadCodeInWhileBody(int $n): void
{
    while ($n > 0) {
        break;
        $x = 1;
    }
}

function deadCodeInDoWhileBody(int $n): void
{
    do {
        break;
        $x = 1;
    } while ($n > 0);
}

function deadCodeInForeachBody(array $items): void
{
    foreach ($items as $item) {
        continue;
        $x = 1;
    }
}

function deadCodeInSwitchCase(int $n): int
{
    switch ($n) {
        case 1:
            return 1;
            $x = 2;

        default:
            return 0;
    }
}

function deadCodeInTryCatchFinally(): int
{
    try {
        throw new \RuntimeException;
        $x = 1;
    } catch (\RuntimeException $e) {
        return 1;
        $x = 2;
    } finally {
        return 2;
        $x = 3;
    }
}

function multiLineShortTernaryWithLiteralFalseCondition(): int
{
    return false
        ?: 1;
}

return 1;

$x = 2;
