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
        foreach ($this->typeRoutes($method) as $route) {
            if ($route->match($path, $data)) {
                return $route->exec($data);
            }
        }

        foreach ($this->groupRoutes as $group) {
            if ($group->match($path, $method, $route, $data)) {
                return $route->exec($data);
            }
        }

        throw new RoutingException('Route for path `' . $path . '` not found.');
    }

    protected function getRoute(string $name): RouteStrategy
    {
        if ($route = $this->find($name)) {
            return $route;
        }

        throw new RoutingException('Route `' . $name . '` not found.');
    }
}
