<?php

declare(strict_types=1);

// Integer in comments represent the branch index difference
// relative to the previous line

$var1               // +1
    =               // 0
    1               // 0
;                   // 0

function simple()   // +1
{                   // 0
    return 1;       // 0
}                   // 0

$var2 = 1;          // -1

if (                // 0
    false           // 0
)                   // 0
{                   // 0
    $var2 += 1;     // +2
}                   // -2

function withIf()   // +3
{                   // 0
    $var = 1;       // 0
    if (false) {    // 0
        $var += 2;  // +1
    }               // -1
    return $var;    // 0
}                   // 0

class MyClass
{
    public              // +2
    function            // 0
    __construct         // 0
    (                   // 0
        $var            // 0
        =               // 0
        1               // 0
    )                   // 0
    {                   // 0
        $var = 1;       // 0
        if (false) {    // 0
            $var += 2;  // +1
        }               // -1
    }                   // 0
    public function withForeach()           // +2
    {                                       // 0
        $var = 1;                           // 0
        foreach ([] as $value);             // 0
        foreach ([] as $value) $var += 2;   // 0
        foreach ([] as $value) {            // 0
            $var += 2;                      // +1
        }                                   // -1
        foreach ([] as $value):             // 0
            $var += 2;                      // +2
        endforeach;                         // -2
        foreach ([] as $value) { $var +=2;  // 0
            $var += 2;                      // +3
        $var += 2; }                        // -3
        foreach (                           // 0
            []                              // 0
            as                              // 0
            $key                            // 0
            =>                              // 0
            $value                          // 0
        )                                   // 0
        {                                   // 0
            $var += 2;                      // +4
        }                                   // -4
    }                                       // 0
    public function withWhile()             // +5
    {                                       // 0
        $var = 1;                           // 0
        while (1 === $var);                 // 0
        while (1 === $var) ++$var;          // 0
        while (1 === $var) {                // 0
            ++$var;                         // +1
        }                                   // -1
        while (1 === $var) { ++$var;        // 0
            ++$var;                         // +2
        ++$var; }                           // -2
        while (1 === $var):                 // 0
            ++$var;                         // +3
        endwhile;                           // -3
        while (                             // 0
            1                               // 0
            ===                             // 0
            $var                            // 0
        )                                   // 0
        {                                   // 0
            ++$var;                         // +4
        }                                   // -4
    }                                       // 0
    public function withIfElseifElse()      // +5
    {                                       // 0
        $var = 1;                           // 0
        if (0 === $var);                    // 0
        if (0 === $var) { ++$var; }         // 0
        if (1 === $var):                    // 0
            ++$var;                         // +1
        elseif (1 === $var):                // -1
            ++$var;                         // +2
        else:                               // -2
            ++$var;                         // +3
        endif;                              // -3
        if (1 === $var) {                   // 0
            ++$var;                         // +4
        } elseif (1 === $var) {             // -4
            ++$var;                         // +5
        } else {                            // -5
            ++$var;                         // +6
        }                                   // -6
        if (1 === $var) { ++$var;           // 0
            ++$var;                         // +7
        } elseif (1 === $var) { ++$var;     // -7
            ++$var;                         // +8
        ++$var; } else { ++$var;            // -8
            ++$var;                         // +9
        }                                   // -9
        if (                                // 0
            1 === $var                      // 0
        )                                   // 0
        {                                   // 0
            ++$var;                         // +10
        }                                   // -10
        elseif                              // 0
        (                                   // 0
            1 === $var                      // 0
        )                                   // 0
        {                                   // 0
            ++$var;                         // +11
        }                                   // -11
        else                                // 0
        {                                   // 0
            ++$var;                         // +12
        }                                   // -12
    }                                       // 0
}

//$myClass = new MyClass();
//foreach (get_class_methods($myClass) as $method) {
//    $myClass->{$method}();
//}