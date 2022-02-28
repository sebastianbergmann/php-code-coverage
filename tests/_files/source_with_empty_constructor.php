<?php

class Foo
{
    public 
        function 
            __construct
    (
        public
        $var
        =
        1
    )
    {
        
    }
}

class Bar
{
    public 
        function 
            __construct
    (
        $var
        =
        1
    )
    {
        $var = 1;
    }
}
