<?php

// Disjunctive Normal Form (DNF)
class MyDnf
{
    public function bar((A&B)|null $entity)
    {
        return $entity;                     // +1
    }
}

// Const in traits
trait MyConstInTrait
{
    public const MY_TRAIT_CONST = 1;
}