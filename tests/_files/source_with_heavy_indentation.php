<?php

class AType {
    const A = 1;
    const B = 2;
}

class Foo
{
    private $state = 1;

    public function isOne(): bool
    {
        $v1
            =
            1
        ;

        $v2
            =
            (bool)
            1
        ;

        $v3
            =
            (bool)
            AType::OPT
        ;

        return
            AType::A === $this->state
            or (
                $this->isBar()
                and \in_array($this->state, [
                    AType::A,
                    AType::B,
                ], true)
            )
            or (\in_array($this->type, [BType::X, BType::Y], true)
                and \in_array($this->state, [
                    AType::C,
                    AType::D,
                    AType::toOutput($this->state),
                ], true))
            ||
            \in_array
                (
                    1
                    ,
                    [
                        AType::A
                        ,
                        2
                        ,
                        $v2
                            =
                            PHP_INT_MAX
                        ,
                        $this
                            ->
                            state
                        ,
                        $v3
                            =
                            1
                        =>
                            2
                        ,
                        uniqid()
                        =>
                            true
                        ,
                        self
                            ::
                            $state
                    ]
                    ,
                    (bool)
                    AType::A
                )
            ;
    }

    public function isTwo(): bool
    {
        return \in_array($this->state, [
            AType::A,
            AType::B,
        ], true);
    }

    public function variable(): bool
    {
        $xa = $this->isOne();
        $xb = 1;
        $xc = $xa;

        $va
            =
        $vb
            =
        [
            $xa,
            $xb,
            1
            +
            $xb
            + 1,
        ];

        [
            $v2,
            $v3
        ]
        =
        u(
            $xa,
            $xb,
            1
            +
            $xc
            +
            1
        );

        return $v2 === $v3;
    }

    public function noElse(): int
    {
        $res = 0;
        if (mt_rand() === 0) {
            return 1;
        } elseif (mt_rand() === 0) {
            return 2;
        } else {
            $res = 3;
        }

        return $res;
    }

    public function noDo(): int
    {
        $res = 0;
        do {
            if (mt_rand() !== 0) {
                $res++;
            }
        } while (
            $res
            <
            10
        );

        return $res;
    }

    public function noTryNoFinally(): int
    {
        $res = 0;
        try {
            if (mt_rand() === 0) {
                throw new \Exception();
            }
        } catch (
            \x\y
            |
            \x\z
            $e
        ) {
            $res = 1;
        } catch (\Error $e) {
            $res = 1;
        } finally {
            $res += 10;
        }

        return $res;
    }

    public function switch(): int
    {
        switch (
            'x'
            .
            mt_rand()
            . 'x'
        ) {
            case
                <<<EOF
                    x1x
                    EOF:
                $res = 'a';

                break;
            case 'x2x':
            case 'x3x':
                $res = 'a';

                break;
            default:
                $res = 'c';
        }

        return $res;
    }

    private static $staticState = 1;
    private const CONST_STATE = 1.1;
}

function &foo($bar)
{
    $baz
        =
        function () {}
    ;
    $a
        =
        true
            ?
            true
            :
            false
    ;
    $b
        =
        "{$a}"
    ;
    $c
        =
        "${b}"
    ;
}
final class BarS
{
    public
    function
    foo
    (
        int
        $var
        =
        1
    )
    {
        if (
            !
            $var
            &&
            []
            ===
            (
            $columnCollection
                =
                []
            )
            &&
            isset
            (
                $columnCollection
                [
                $key
                ]
                [
                $key
                ]
            )
        ) {
            $dataType
                =
                $columnCollection
                [
                $key
                ]
            ;

            $obj
                ->
                method1
                (
                )
                ->
                method2
                (
                )
            ;

            (string)
            $var
            ;
        }
    }
}
