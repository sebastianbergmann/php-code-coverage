<?php

class Foo
{
    public function one(): array
    {
        return [
            '...',
            '...',
        ];
    }

    public function two(): array
    {
        return ['...',
            '...',
        ];
    }

    public function three(): array
    {
        return [

            '...',
        ];
    }

    public function four(): bool
    {
        return in_array('...', [
            '...',
            '...',
        ], true);
    }

    public function fifth(): array
    {
        return
            [
            ]
        ;
    }

    public function sixth(): int
    {
        return
            1
        ;
    }

    public function seventh(): string
    {
        return
            self
            ::
            class
        ;
    }

    public function eigth(): void
    {
        return
            ;
    }
}
