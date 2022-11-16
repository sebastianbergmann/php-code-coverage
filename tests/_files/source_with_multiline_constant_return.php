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

    public function nowdoc(): string
    {
        return
            <<<'EOF'
                foo
                EOF
        ;
    }

    public function nowdocConcatA(): string
    {
        return '' .
            <<<'EOF'
                foo
                EOF;
    }

    public function nowdocConcatB(): string
    {
        return ''
            . <<<'EOF'
                foo
                EOF;
    }

    public function nowdocConcatC(): string
    {
        return <<<'EOF'
                foo
                EOF
            . '';
    }

    public function nowdocConcatNested(): string
    {
        return (<<<'EOF'
                foo
                EOF
            . <<<'EOF'
                foo
                EOF)
            . (<<<'EOF'
                foo
                EOF
            . <<<'EOF'
                foo
                EOF);
    }

    public function complexAssociativityRight(): int
    {
        return
            1
            **
            2
            **
            3
        ;
    }

    public function complexAssociativityNa(): bool
    {
        return
            !
            !
            !
            false
        ;
    }

    public function complexTernary(): int
    {
        return
            1
            ? (
                2
                ? 3
                : 4
            )
            : 5
        ;
    }

    public function constFromArray(): string
    {
        return [
            'foo',
            'bar',
            'ro',
            'fi',
            'omega',
        ]
        [2]
        ;
    }

    public function withNotConstInTheMiddle(): string
    {
        return
            ''
            .
            ''
            .
            phpversion()
            .
            ''
            .
            ''
        ;
    }

    public function multipleConcats(): string
    {
        return
            'a'
            .
            'b'
            .
            'c'
            .
            'd'
            .
            'e'
        ;
    }

    public function multilineHeredoc(): string
    {
        return <<<EOF
a
b
c
EOF;

    }

    public function unaryLogicalNotWithNotConstInTheMiddle(): bool
    {
        return !
        (
            ''
            .
            phpversion()
            .
            ''
        );
    }

    public function unaryMinusWithNotConstInTheMiddle(): float
    {
        return -
        (
            ''
            .
            phpversion()
            .
            ''
        );
    }

    public function unaryCastWithNotConstInTheMiddle(): int
    {
        return (int)
        (
            ''
            .
            phpversion()
            .
            ''
        );
    }

    public function complexArrayWithNotConstInTheMiddle(): array
    {
        return [
            [
                1,
                phpversion(),
                1,
            ],
            [
                [
                    1,
                ],
            ],
            [
                // empty array with comment
            ],
            [
                phpversion(),
                1,
            ],
            [
                1,
                phpversion(),
            ],
            phpversion(),
        ];
    }

    public function constFromArrayWithNotConstInTheMiddle(): string
    {
        return [
            'foo',
            'bar',
            'ro',
            'fi' => 'fi_v',
            'omega',
        ]
        [
        'f'
        . 'i'
        ]
        ;
    }

    public function emptyArray(): array
    {
        return
        (
            [
            ]
        )
        ;
    }

    public function complexAssociativityNa2(): bool
    {
        return
            !
            !
            !
            <<<'EOF'
                foo
                foo
                EOF
        ;
    }

    public function unaryMinusNowdoc(): float
    {
        return
            -
            <<<'EOF'
                1.
                2
                EOF
        ;
    }

    public
    function
    emptyMethod
    (
    )
    :
    void
    {
    }

    public function emptyMethodWithComment(): void
    {
        // empty method with comment
    }

    public function simpleConstArrayEmpty(): array
    {
        return
        [
            // empty array with comment
        ];
    }

    public function nestedConstArrayEmpty(): array
    {
        return
        [
            [
                // empty subarray with comment
            ]
        ];
    }

    public function nestedConstArrayOne(): array
    {
        return
        [
            [
                <<<'EOF'
                    1
                    EOF,
            ]
        ];
    }

    public function nestedConstArrayTwo(): array
    {
        return
        [
            [
                <<<'EOF'
                    1
                    EOF,
                2,
            ]
        ];
    }

    public function nestedArrayWithExecutableInKey(): array
    {
        return
        [
            [
                1,
                phpversion()
                    =>
                        2,
            ]
        ];
    }
}
