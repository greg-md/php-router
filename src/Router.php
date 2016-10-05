<?php

namespace Greg\Router;

use Greg\Support\InternalTrait;

class Router
{
    use RouterTrait, InternalTrait;

    public function createRoute($format, $action, array $settings = [])
    {
        return $this->_createRoute($format, $action, $settings)->setRouter($this);
    }
}
