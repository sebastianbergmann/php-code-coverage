<?php

class Foo
{
    public function isOne(): bool
    {
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
            ;
    }

    public function isTwo(): bool
    {
        return \in_array($this->state, [
            AType::A,
            AType::B,
        ], true);
    }
}
