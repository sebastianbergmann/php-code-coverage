<?php

class Foo
{
    public
        function
            __construct(
                string
                $bar
    )
    {
        match
        (
            $bar
        )
        {
            'a',
            'b'
            =>
            1
        ,
            // Some comments
            default
            =>
            throw new Exception()
        ,
        }
        ;
    }
}
