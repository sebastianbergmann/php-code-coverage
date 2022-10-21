<?php

class Foo
{
    public function BitwiseAnd(): int
    {
        return
            1
            &
            1
        ;
    }

    public function BitwiseOr(): int
    {
        return
            1
            |
            1
        ;
    }

    public function BitwiseXor(): int
    {
        return
            1
            ^
            1
        ;
    }

    public function BooleanAnd(): bool
    {
        return
            true
            &&
            false
        ;
    }

    public function BooleanOr(): bool
    {
        return
            false
            ||
            true
        ;
    }

    public function Coalesce(): bool
    {
        return
            true
            ??
            false
        ;
    }

    public function Concat(): string
    {
        return
            'foo'
            .
            'bar'
        ;
    }

    public function Div(): int
    {
        return
            2
            /
            1
        ;
    }

    public function Equal(): bool
    {
        return
            2
            ==
            1
        ;
    }

    public function Greater(): bool
    {
        return
            2
            >
            1
        ;
    }

    public function GreaterOrEqual(): bool
    {
        return
            2
            >=
            1
        ;
    }

    public function Identical(): bool
    {
        return
            2
            ===
            1
        ;
    }

    public function LogicalAnd(): bool
    {
        return
            true
            and
            false
        ;
    }

    public function LogicalOr(): bool
    {
        return
            true
            or
            false
        ;
    }

    public function LogicalXor(): bool
    {
        return
            true
            xor
            false
        ;
    }

    public function Minus(): int
    {
        return
            2
            -
            1
        ;
    }

    public function Mod(): int
    {
        return
            2
            %
            1
        ;
    }

    public function Mul(): int
    {
        return
            2
            *
            1
        ;
    }

    public function NotEqual(): bool
    {
        return
            2
            !=
            1
        ;
    }

    public function NotIdentical(): bool
    {
        return
            2
            !==
            1
        ;
    }

    public function Plus(): int
    {
        return
            2
            +
            1
        ;
    }

    public function Pow(): int
    {
        return
            2
            **
            1
        ;
    }

    public function ShiftLeft(): int
    {
        return
            2
            <<
            1
        ;
    }

    public function ShiftRight(): int
    {
        return
            2
            >>
            1
        ;
    }

    public function Smaller(): bool
    {
        return
            2
            <
            1
        ;
    }

    public function SmallerOrEqual(): bool
    {
        return
            2
            <=
            1
        ;
    }

    public function Spaceship(): int
    {
        return
            2
            <=>
            1
        ;
    }
}
