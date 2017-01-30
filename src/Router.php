<?php

namespace Greg\Routing;

use Greg\Support\Url;

class Router extends RoutesAbstract
{
    public function url(string $name, array $params = []): string
    {
        return $this->getRoute($name)->fetch($params);
    }

    public function urlAbsolute(string $name, array $params = []): string
    {
        return Url::absolute($this->url($name, $params));
    }
}
