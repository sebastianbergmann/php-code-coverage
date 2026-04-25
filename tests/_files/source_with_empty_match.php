<?php declare(strict_types=1);
function emptyMatch(int $x): mixed
{
    return match ($x) {};
}
