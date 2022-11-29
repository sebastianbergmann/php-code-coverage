<?php

declare(strict_types=1);

// Integer in comments represent the branch index difference
// relative to the previous line

$var1               // +1
    =               // 0
    1               // 0
;                   // 0

function empty1()
{
}                   // +1
function empty2(){
}                   // +1

function simple1()
{
    return 1;       // +1
}
function simple2(){
    return 1;       // +1
}

$var2 = 1;          // -4

if (                // 0
    false           // 0
)                   // 0
{                   // 0
    $var2 += 1;     // +5
}                   // -5

function withIf()
{
    $var = 1;       // +6
    if (false) {    // 0
        $var += 2;  // +1
    }               // -1
    return $var;    // 0
}

/**
 * @internal
 */
class MyClass
{
    public const C1 = 1;
    public $var1 = 1;
    public
    function
    __construct
    (
        &
        $var
        =
        1
    )
    {
        $var = 1;       // +2

        if (false) {    // 0
            $var += 2;  // +1
        }               // -1
    }
    public function myEmpty()
    {
    }                                       // +2
    public function withForeach()
    {
        $var = 1;                           // +1
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
    }
    public function withWhile()
    {
        $var = 1;                           // +5
        while (0 === $var);                 // 0
        while (0 === $var) ++$var;          // 0
        while (0 === $var) {                // 0
            ++$var;                         // +1
        }                                   // -1
        while (0 === $var) { ++$var;        // 0
            ++$var;                         // +2
        ++$var; }                           // -2
        while (0 === $var):                 // 0
            ++$var;                         // +3
        endwhile;                           // -3
        while (                             // 0
            0                               // 0
            ===                             // 0
            $var                            // 0
        )                                   // 0
        {                                   // 0
            ++$var;                         // +4
        }                                   // -4
    }
    public function withIfElseifElse()
    {
        $var = 1;                           // +5
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
    }
    public function withFor()
    {
        $var = 1;                           // +13
        for (;false;);                      // 0
        for (;false;) $var += 2;            // 0
        for (;false;) {                     // 0
            $var += 2;                      // +1
        }                                   // -1
        for (;false;):                      // 0
            $var += 2;                      // +2
        endfor;                             // -2
        for (;false;) { $var +=2;           // 0
            $var += 2;                      // +3
        $var += 2; }                        // -3
        for (                               // 0
            $inc = 0;                       // 0
            false;                          // 0
            ++$inc                          // 0
        )                                   // 0
        {                                   // 0
            $var += 2;                      // +4
        }                                   // -4
    }
    public function withDoWhile()
    {
        $var = 1;                           // +5
        do {} while (0 === $var);           // 0
        do ++$var; while (0 === $var);      // 0
        do                                  // 0
            ++$var;                         // 0
        while (0 === $var);                 // 0
        do {                                // 0
            ++$var;                         // 0
        } while (0 === $var);               // 0
        do { ++$var;                        // 0
            ++$var;                         // 0
        ++$var; } while (0 === $var);       // 0
        do {                                // 0
            ++$var;                         // 0
        }                                   // 0
        while                               // 0
        (                                   // 0
            0                               // 0
            ===                             // 0
            $var                            // 0
        )                                   // 0
        ;                                   // 0
    }
    public function withSwitch()
    {
        $var = 1;                           // +1
        switch ($var) {                     // 0
            case 0:                         // 0
            case 1:                         // 0
                ++$var;                     // +1
                break;                      // 0
            case 2:                         // -1
                ++$var;                     // +2
            case 3:                         // -2
                ++$var;                     // +3
                break;                      // 0
            default:                        // -3
                ++$var;                     // +4
        }                                   // -4
        switch ($var):                      // 0
            case 0:                         // 0
            case 1:                         // 0
                ++$var;                     // +5
                break;                      // 0
            case 2:                         // -5
                ++$var;                     // +6
            case 3:                         // -6
                ++$var;                     // +7
                break;                      // 0
            default:                        // -7
                ++$var;                     // +8
        endswitch;                          // -8
    }
    public function withMatch()
    {
        $var = 1;                           // +9
        $var2 = match ($var) {              // 0
            0 => ++$var,                    // +1
            1 => ++$var,                    // +1
            default => ++$var,              // +1
        };                                  // -3
        $var2                               // 0
            =                               // 0
            match                           // 0
            (                               // 0
            $var                            // 0
            )                               // 0
            {                               // 0
                0                           // 0
                =>                          // 0
                ++$var                      // +4
            ,                               // -4
                1                           // 0
                =>                          // 0
                ++$var                      // +5
            ,                               // -5
                default                     // 0
                =>                          // 0
                ++$var                      // +6
            ,                               // -6
        }                                   // 0
        ;                                   // 0
    }
    public function withReturn()
    {
        $var = 1;                           // +7
        if (false) {                        // 0
            ++$var;                         // +1
            return                          // 0
                $var                        // 0
            ;                               // 0
            ++$var;                         // +1
            if (false) {                    // 0
                ++$var;                     // +1
            }                               // -1
        }                                   // -2
        return;                             // 0
        ++$var;                             // +4
    }
    public function withContinue()
    {
        $var = 1;                           // +1
        for ($i = 0; $i < 10; $i++) {       // 0
            if (false) {                    // +1
                ++$var;                     // +1
                continue                    // 0
                    1                       // 0
                ;                           // 0
                ++$var;                     // +1
            }                               // -2
            ++$var;                         // 0
            continue;                       // 0
            ++$var;                         // +3
        }                                   // -4
    }
    public function withBreak()
    {
        $var = 1;                           // +5
        for ($i = 0; $i < 10; $i++) {       // 0
            if (false) {                    // +1
                ++$var;                     // +1
                break                       // 0
                    1                       // 0
                ;                           // 0
                ++$var;                     // +1
            }                               // -2
            ++$var;                         // 0
            break;                          // 0
            ++$var;                         // +3
        }                                   // -4
    }
    public function withGoto()
    {
        $var = 1;                           // +5
        if (false) {                        // 0
            ++$var;                         // +1
            goto                            // 0
            a                               // 0
            ;                               // 0
            ++$var;                         // +1
        }                                   // -2
        ++$var;                             // 0
        a                                   // +3
        :                                   // 0
        ++$var;                             // 0
        b:                                  // +1
        ++$var;                             // 0
    }
    public function withThrow()
    {
        $var = 1;                           // +1
        try {                               // 0
            ++$var;                         // +1
            throw                           // 0
            new                             // 0
            \Exception()                    // 0
            ;                               // +2
            ++$var;                         // -1
        } catch (\Exception $exception) {   // +2
            ++$var;                         // 0
        } catch (\RuntimeException $re) {   // +1
        } catch (\Throwable $throwable) {   // +1
            ++$var;                         // 0
        } finally {                         // +1
            ++$var;                         // 0
        }                                   // -7
        ++$var;                             // 0
    }
    public function withTernaryOperator()
    {
        $var                                // +8
            =                               // 0
            true                            // 0
            ?                               // 0
                'a'                         // +1
                :                           // -1
                'b'                         // +2
            ;                               // -2
    }
    public function withCall()
    {
        $var = 1;                           // +3
        $var = intval($var);                // 0
        ++$var;                             // +1
        $date = new DateTimeImmutable();    // 0
        ++$var;                             // +1
        $ymd = $date->format('Ymd');        // 0
        ++$var;                             // +1
        $ymd = $date?->format('Ymd');       // 0
        ++$var;                             // +1
        $date = DateTime::createFromImmutable($date);       // 0
        ++$var;                             // +1
    }
    public function withClosure()
    {
        $myf = function(){};                // +1
        $myf = function(){                  // 0
        };                                  // +1
        $myf = function()                   // -1
        {                                   // 0
        };                                  // +2
        $myf = function(){                  // -2
            return 1;                       // +3
        };                                  // -3
        $myf = function()                   // 0
        {                                   // 0
            return 1;                       // +4
        };                                  // -4
        $var = 1;                           // 0
        $myf                                // 0
            =                               // 0
            function                        // 0
            (                               // 0
                $var2                       // 0
                =                           // 0
                2                           // 0
            )                               // 0
                use                         // 0
                (                           // 0
                    &                       // 0
                    $var                    // 0
                )                           // 0
            :                               // 0
            void                            // 0
        {                                   // 0
        };                                  // +5
        $myf = function(){ $var = 1;};      // -5
    }
    public function withArrowFn()
    {
        $y = 1;                             // +6
        $fn1 = fn($x) => $x + $y;           // 0
        $fn1 = fn($x) =>                    // 0
            $x + $y;                        // +1
    }
    public function withComments()
    {
        /** @var int $var */
        $var = 1;                           // +1
        /** @var int $var */
        $var = 2;                           // 0
        // C3
        $var = 3;                           // 0
        # C4
        $var = 3;                           // 0
        /* @var int $var */
        $var = 5;                           // 0
        // Comment2
    }
    public function withCommentsOnly()
    {
        /**
         $var = 1;
         */
    }                                       // +1
}
