<?php

namespace Greg\Router;

class Router
{
    use RouterTrait;

    protected function createRoute($format, $action = null, $name = null)
    {
        return $this->newRoute($format, $action, $name)->setRouter($this);
    }
}
