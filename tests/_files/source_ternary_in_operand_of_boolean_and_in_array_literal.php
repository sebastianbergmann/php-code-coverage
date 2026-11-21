<?php declare(strict_types=1);

function row(bool $a, bool $b, int $c): array
{
    return ['key' => $a && ($b
        ? \max($c, 1)
        : \min($c, 1))];
}
