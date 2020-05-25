<?php

class MyClass implements AnInterface
{
    public function m(): void
    {
        $o = new stdClass;

        array_filter(
            ['a' => 1, 'b' => 2, 'c' => 3, 'd' => 4],
            static function ($v, $k)
            {
                return $k === 'b' || $v === 4;
            },
            ARRAY_FILTER_USE_BOTH
        );
    }
}

interface AnInterface
{
    public function m(): void;
}
