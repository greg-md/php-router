<?php

namespace Greg\Routing;

class Router extends RoutesAbstract
{
    public function url(string $name, array $params = []): string
    {
        return $this->getRoute($name)->url($params);
    }

    public function dispatch(string $path, ?string $method = null): string
    {
        if (!$route = $this->detect($path, $method, $data)) {
            throw new RoutingException('Route for path `' . $path . '` not found.');
        }

        return $route->exec($data);
    }

    protected function getRoute(string $name): RouteStrategy
    {
        if ($route = $this->find($name)) {
            return $route;
        }

        throw new RoutingException('Route `' . $name . '` not found.');
    }
}
