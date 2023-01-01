<?php

// Match
$var = 1;                           // +1
$var2 = match ($var) {              // +1
    0 => ++$var,                    // 0
    1 => ++$var,                    // 0
    default => ++$var,              // 0
};                                  // 0
$var2                               // +1
    =                               // 0
    match                           // 0
    (                               // 0
    $var                            // 0
    )                               // 0
    {                               // 0
        0                           // 0
        =>                          // 0
        ++$var                      // 0
    ,                               // 0
        1,                          // 0
        2                           // 0
        =>                          // 0
        ++$var                      // 0
    ,                               // 0
        default                     // 0
        =>                          // 0
        ++$var                      // 0
    ,                               // 0
}                                   // 0
;                                   // 0

// Nullsafe Operator
$ymd = $date?->format('Ymd');       // +1
++$var;                             // +1

// Union types
interface MyUnion
{
    public function getNameIdentifier(): ?string;
    public function hasClaim(bool|string $type, mixed $value): bool;
    public function getClaims($type1 = null): array;
}
