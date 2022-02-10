<?php

class Foo
{
    private $state = 1;

    public function isOne(): bool
    {
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

    private static $staticState = 1;
    private const CONST_STATE = 1.1;
}
