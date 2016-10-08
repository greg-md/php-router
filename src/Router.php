<?php

namespace Greg\Router;

use Greg\Support\IoC\IoCManagerInterface;

class Router
{
    use RouterTrait;

    public function __construct(IoCManagerInterface $ioCManager = null)
    {
        if ($ioCManager) {
            $this->setIoCManager($ioCManager);
        }

        return $this;
    }

    public function createRoute($format, $action)
    {
        return (new Route($format, $action, $this->getIoCManager()))->setRouter($this);
    }
}
