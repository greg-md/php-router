<?php

namespace Greg\Routing;

class Router extends RoutesAbstract
{
    public function url(string $name, array $params = []): string
    {
        return $this->getRoute($name)->url($params);
    }
}
