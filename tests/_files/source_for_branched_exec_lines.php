<?php

declare(strict_types=1);

namespace MyNamespace;

use DateTimeInterface;

use MyOtherNamespace\{ClassA, ClassB, ClassC as C};
use function MyOtherNamespace\{fn_a, fn_b, fn_c};
use const MyOtherNamespace\{ConstA, ConstB, ConstC};

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

$var2 = 1;          // +1

if (false) {        // +1
    $var2 += 1;     // +1
}

function withIf()
{
    $var = 1;       // +1
    if (false) {    // +1
        $var += 2;  // +1
    }
    return $var;    // +1
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
        $var = 1;       // +3

        if (false) {    // +1
            $var += 2;  // +1
        }
    }
    public function myEmpty()
    {
    }                                       // +1
    public function withForeach()
    {
        $var = 1;                           // +1
        foreach ([] as $value);             // +1
        foreach ([] as $value) $var += 2;   // +1
        foreach ([] as $value) {            // +1
            $var += 2;                      // +1
        }
        foreach ([] as $value):             // +1
            $var += 2;                      // +1
        endforeach;
        foreach ([] as $value) { $var +=2;  // +1
            $var += 2;                      // +1
        $var += 2; }                        // +1
        foreach (
            []                              // +1
            as                              // 0
            $key                            // 0
            =>                              // 0
            $value                          // 0
        )
        {
            $var += 2;                      // +1
        }
    }
    public function withWhile()
    {
        $var = 1;                           // +1
        while (0 === $var);                 // +1
        while (0 === $var) ++$var;          // +1
        while (0 === $var) {                // +1
            ++$var;                         // +1
        }
        while (0 === $var) { ++$var;        // +1
            ++$var;                         // +1
        ++$var; }                           // +1
        while (0 === $var):                 // +1
            ++$var;                         // +1
        endwhile;
        while (
            0                               // +1
            ===                             // 0
            $var                            // 0
        )
        {
            ++$var;                         // +1
        }
    }
    public function withIfElseifElse()
    {
        $var = 1;                           // +1
        if (0 === $var);                    // +1
        if (0 === $var) { ++$var; }         // +1
        if (1 === $var):                    // +1
            ++$var;                         // +1
        elseif (1 === $var):                // +1
            ++$var;                         // +1
        else:
            ++$var;                         // +1
        endif;
        if (1 === $var) {                   // +1
            ++$var;                         // +1
        } elseif (1 === $var) {             // +1
            ++$var;                         // +1
        } else {
            ++$var;                         // +1
        }
        if (1 === $var) { ++$var;           // +1
            ++$var;                         // +1
        } elseif (1 === $var) { ++$var;     // +1
            ++$var;                         // +1
        ++$var; } else { ++$var;            // +1
            ++$var;                         // +1
        }
        if (
            1 === $var                      // +1
        )
        {
            ++$var;                         // +1
        }
        elseif
        (
            1 === $var                      // +1
        )
        {
            ++$var;                         // +1
        }
        else
        {
            ++$var;                         // +1
        }
    }
    public function withFor()
    {
        $var = 1;                           // +1
        for (;false;);                      // +1
        for (;false;) $var += 2;            // +1
        for (;false;) {                     // +1
            $var += 2;                      // +1
        }
        for (;false;):                      // +1
            $var += 2;                      // +1
        endfor;
        for (;false;) { $var +=2;           // +1
            $var += 2;                      // +1
        $var += 2; }                        // +1
        for (
            $inc = 0;                       // +1
            false;                          // 0
            ++$inc                          // 0
        )
        {
            $var += 2;                      // +1
        }
    }
    public function withDoWhile()
    {
        $var = 1;                           // +1
        do {} while (0 === $var);           // +1
        do ++$var; while (0 === $var);      // +1
        do
            ++$var;                         // +2
        while (0 === $var);                 // -1
        do {
            ++$var;                         // +3
        } while (0 === $var);               // -1
        do { ++$var;                        // +3
            ++$var;                         // +1
        ++$var; } while (0 === $var);       // -2
        do {
            ++$var;                         // +4
        }
        while
        (
            0                               // -1
            ===                             // 0
            $var                            // 0
        )
        ;
    }
    public function withSwitch()
    {
        $var = 1;                           // +2
        switch ($var) {
            case 0:                         // +1
            case 1:                         // +1
                ++$var;                     // +1
                break;                      // +1
            case 2:                         // +1
                ++$var;                     // +1
            case 3:                         // +1
                ++$var;                     // +1
                break;                      // +1
            default:
                ++$var;                     // +1
        }
        switch ($var):
            case 0:                         // +1
            case 1:                         // +1
                ++$var;                     // +1
                break;                      // +1
            case 2:                         // +1
                ++$var;                     // +1
            case 3:                         // +1
                ++$var;                     // +1
                break;                      // +1
            default:
                ++$var;                     // +1
        endswitch;
    }
    public function withReturn()
    {
        $var = 1;                           // +1
        if (false) {                        // +1
            ++$var;                         // +1
            return                          // +1
                $var                        // 0
            ;                               // 0
            ++$var;                         // +1
            if (false) {                    // +1
                ++$var;                     // +1
            }
        }
        return;                             // +1
        ++$var;                             // +1
    }
    public function withContinue()
    {
        $var = 1;                           // +1
        for ($i = 0; $i < 10; $i++) {       // +1
            if (false) {                    // +1
                ++$var;                     // +1
                continue                    // +1
                    1                       // 0
                ;                           // 0
                ++$var;                     // +1
            }
            ++$var;                         // +1
            continue;                       // +1
            ++$var;                         // +1
        }
    }
    public function withBreak()
    {
        $var = 1;                           // +1
        for ($i = 0; $i < 10; $i++) {       // +1
            if (false) {                    // +1
                ++$var;                     // +1
                break                       // +1
                    1                       // 0
                ;                           // 0
                ++$var;                     // +1
            }
            ++$var;                         // +1
            break;                          // +1
            ++$var;                         // +1
        }
    }
    public function withGoto()
    {
        $var = 1;                           // +1
        if (false) {                        // +1
            ++$var;                         // +1
            goto                            // +1
            a                               // 0
            ;                               // 0
            ++$var;                         // +1
        }
        ++$var;                             // +1
        a
        :
        ++$var;                             // +1
        b:
        ++$var;                             // +1
    }
    public function withThrow()
    {
        $var = 1;                           // +1
        try {
            ++$var;                         // +1
            throw
            new                             // +2
            \Exception()                    // 0
            ;
            $myex = new \Exception();       // +1
            throw
                $myex                       // +1
            ;
            ++$var;                         // +1
        } catch (\Exception $exception) {   // +1
            ++$var;                         // +1
        } catch (\RuntimeException $re) {   // +1
        }
        catch
        (
            \Throwable                      // +1
            $throwable
        )
        {
            ++$var;                         // +1
        } finally {
            ++$var;                         // +1
        }
        ++$var;                             // +1
    }
    public function withTernaryOperator()
    {
        $var = true ? 'a' : 'b';            // +1
        $var                                // +1
            =                               // 0
            true                            // 0
            ?                               // 0
                'a'                         // +1
                :                           // -1
                'b'                         // +2
            ;                               // -2

        $short = $var ?: null;              // +3
        $short = $var                       // +1
            ?: null;                        // +1

        $short = $var ?? null;              // +1
        $short = $var                       // +1
            ?? null;                        // +1
    }
    public function withCall()
    {
        $var = 1;                           // +1
        $var = intval(                      // +1
            $var                            // 0
        );                                  // 0
        $var = time(                        // +1

        );                                  // 0
        $var                                // +1
            =                               // 0
            intval(                         // 0
            $var                            // 0
        );                                  // 0
        ++$var;                             // +1
        $date = new \DateTimeImmutable();   // +1
        $date                               // +1
            =                               // 0
            new                             // 0
            \DateTimeImmutable              // 0
            (                               // 0
                'now'                       // 0
            )                               // 0
        ;                                   // 0
        ++$var;                             // +1
        $ymd = $date->format('Ymd');        // +1
        $ymd                                // +1
            =                               // 0
            $date                           // 0
            ->format(                       // 0
                'Ymd'                       // 0
            )                               // 0
        ;                                   // 0
        ++$var;                             // +1
        $date = \DateTime::createFromImmutable($date);       // +1
        $date                               // +1
            =                               // 0
            \DateTimeImmutable              // 0
                ::                          // 0
                createFromMutable           // 0
                (                           // 0
                    $date                   // 0
                )                           // 0
        ;                                   // 0
        ++$var;                             // +1
    }
    public function withClosure()
    {
        $myf = function(){};                // +1
        $myf = function(){                  // +1
        };                                  // +1
        $myf = function()                   // +1
        {                                   // 0
        };                                  // +1
        $myf = function(){                  // +1
            return 1;                       // +1
        };                                  // -1
        $myf = function()                   // +2
        {                                   // 0
            return 1;                       // +1
        };                                  // -1
        $var = 1;                           // +2
        $myf                                // +1
            =                               // 0
            function                        // 0
            (                               // 0
                $var2                       // 0
                =                           // 0
                2,                          // 0
                $var3                       // 0
                =                           // 0
                null                        // 0
            )                               // 0
                use                         // 0
                (                           // 0
                    &                       // 0
                    $var                    // 0
                )                           // 0
            :                               // 0
            void                            // 0
        {                                   // 0
        };                                  // +1
        $myf = function(){ $var = 1;};      // +2
    }
    public function withAnonymousClass()
    {
        $var = 1;                           // +1
        $myClass                            // +1
            =                               // 0
            new                             // 0
            class                           // 0
            extends                         // 0
            \RuntimeException               // 0
            implements                      // 0
            \Throwable                      // 0
            {                               // 0
                private const MY_CONST = 1;
                private $var = 1;
                public function myMethod()
                {
                    return;                 // +3
                }

                public function m1(): void {} // +1
                public function m2(): void {
                }                           // +1
                public function m3(): void
                {}                          // +1
            }                               // -6
        ;                                   // 0
    }
    public function withComments()
    {
        $var = 1;                           // +7
        /** @var int $var */
        $var = 2;                           // +1
        // C3
        $var = 3;                           // +1
        # C4
        $var = 3;                           // +1
        /* @var int $var */
        $var = 5;                           // +1
        $var = [                            // +1
            // within nodes
            new \DateTimeImmutable(),       // 0
            # within nodes
            new \DateTimeImmutable(),       // 0
            /*
             * within nodes
             */
            new \DateTimeImmutable(),       // 0
            /*
             * within nodes
             */
            new \DateTimeImmutable(),       // 0
        ];                                  // 0
        // Comment2
    }
    public function withCommentsOnly()
    {
        /**
         $var = 1;
         */
    }                                       // +1
    public function withEarlyReturns()
    {
        foreach ([] as $value) {            // +1
            $var = 1;                       // +1
            if (false) {                    // +1
                ++$var;                     // +1
                continue;                   // +1
                ++$var;                     // +1
            }
            if (false) {                    // +1
                ++$var;                     // +1
                break;                      // +1
                ++$var;                     // +1
            }
            if (false) {                    // +1
                ++$var;                     // +1
                throw new \Exception();     // +1
                ++$var;                     // +1
            }
            if (false) {                    // +1
                ++$var;                     // +1
                return;                     // +1
                ++$var;                     // +1
            }
            if (false) {                    // +1
                ++$var;                     // +1
                intval(1);            // +1
                ++$var;                     // +1
            }
        }

        return;                             // +1
        $var = 2;                           // +1
    }
    public function withMultilineStrings()
    {
        $var = 1;                           // +1
        $singleQuote =                      // +1
        'start                              // 0
        a
        $var
        z
        end';                               // 0
        $doubleQuote =                      // +1
        "start                              // 0
        a
        $var                                // 0
        z
        end";                               // 0
        $nowDoc =                           // +1
<<<'LINE_ADDED_IN_TEST'
        start
        a
        $var
        z
        end
LINE_ADDED_IN_TEST;                         // 0
        $hereDoc =                          // +1
<<<LINE_ADDED_IN_TEST
        start                               // 0
        a
        $var                                // 0
        z
        end
LINE_ADDED_IN_TEST;                         // 0
    }
}

interface MyInterface
{
    public const MY_INTERFACE_CONST = 1;
    public const MY_INTERFACE_COMPLEX_CONST = [
        1,
        'string',
    ];
    public function myMethod();
    public function multiline(
        \stdClass $var
    ): \stdClass;
    public function multilineVoid(
    ): void;
}

trait MyTrait
{
    public function myTrait()
    {}                                      // +6
}

abstract class MyAbstractClass implements MyInterface
{}
final class MyFinalClass extends MyAbstractClass
{
    use MyTrait;
    public const STRUCT = [
        'foo' => 'bar',
    ];
    private string $var;
    public function m1(): void {}   // +4
    public function m2(): void {
    }                               // +1
    public function m3(): void
    {}                              // +1
    public function m4(): void
    {
    }                               // +1
}
