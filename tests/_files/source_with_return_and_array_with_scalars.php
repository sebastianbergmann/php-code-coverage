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

    public function seventh(): int
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

    /**
     * Array expression must not add executable line to make uncovered
     * output consistent with output from real coverage driver like Xdebug.
     *
     * @see https://github.com/sebastianbergmann/php-code-coverage/pull/938
     */
    public function nineNestedArray(): array
    {
        return [
            [],
            [[]],
            [[
                'test',
                'test' => [
                    [[[false]]]
                ]
            ]],
        ];
    }
}
