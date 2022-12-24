<?php

// Disjunctive Normal Form (DNF)
class MyDnf
{
    public function bar((A&B)|null $entity)
    {
        return $entity;                     // +1
    }
}
