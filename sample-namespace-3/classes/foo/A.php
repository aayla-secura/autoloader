<?php

namespace Foo;

class A
{
    public function __construct()
    {
        echo get_class($this);
    }

}
