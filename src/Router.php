<?php

namespace Greg\Router;

class Router
{
    use RouterTrait;

    public function createRoute($format, $action)
    {
        return (new Route($format, $action))->setRouter($this);
    }
}
