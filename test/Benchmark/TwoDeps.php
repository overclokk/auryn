<?php

namespace Auryn\Benchmark;

class TwoDeps
{
    public function __construct(Noop $foo, Noop $bar)
    {

    }
}
