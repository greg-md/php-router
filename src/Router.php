<?php

namespace Greg\Router;

class Router
{
    use RouterTrait;

    public function createRoute($format, $action, array $settings = [])
    {
        return $this->_createRoute($format, $action, $settings)->setRouter($this);
    }
}
