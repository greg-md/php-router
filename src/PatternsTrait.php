<?php

namespace Greg\Routing;

trait PatternsTrait
{
    private $patterns = [];

    public function pattern($name, $regex)
    {
        $this->patterns[$name] = $regex;
    }
}
