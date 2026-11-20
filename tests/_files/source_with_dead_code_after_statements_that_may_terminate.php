<?php declare(strict_types=1);
namespace SebastianBergmann\CodeCoverage\TestFixture\DeadCodeAfterStatementsThatMayTerminate;

function multiLineShortTernaryWithLiteralTrueCondition(): int
{
    return true
        ?: 1;
}

function doWhileLoopThatLoopsForever(): int
{
    do {
        doSomething();
    } while (true);

    return 1;
}

function doWhileLoopThatLoopsForeverButContainsBreak(bool $b): int
{
    do {
        if ($b) {
            break;
        }
    } while (true);

    return 1;
}

function switchWithoutCases(int $n): int
{
    switch ($n) {
    }

    return 1;
}

function ifWhoseBranchesAllTerminate(bool $a, bool $b): int
{
    if ($a) {
        return 1;
    } elseif ($b) {
        return 2;
    } else {
        return 3;
    }

    return 4;
}

function ifWhoseBodyDoesNotTerminate(bool $a): int
{
    if ($a) {
        doSomething();
    } else {
        return 1;
    }

    return 2;
}

function ifWhoseElseifBodyDoesNotTerminate(bool $a, bool $b): int
{
    if ($a) {
        return 1;
    } elseif ($b) {
        doSomething();
    } else {
        return 2;
    }

    return 3;
}

function ifWhoseElseBodyDoesNotTerminate(bool $a): int
{
    if ($a) {
        return 1;
    } else {
        doSomething();
    }

    return 2;
}

function doSomething(): void
{
}
