<?php

class Foo extends Exception
{
    public function __construct()
    {
        parent::__construct(
            'some message',
            42
        );
    }
}
