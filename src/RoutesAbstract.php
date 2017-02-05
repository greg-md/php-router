<?php

namespace Greg\Routing;

use Greg\Routing\Binder\BindTrait;
use Greg\Support\Arr;
use Greg\Support\Http\Request;
use Greg\Support\Obj;
use Greg\Support\Str;

abstract class RoutesAbstract
{
    use BindTrait, ErrorActionTrait, DispatcherTrait, HostTrait;

    /**
     * @var Route[][]
     */
    protected $routes = [];

    /**
     * @var GroupRoute[]
     */
    protected $groupRoutes = [];

    /**
     * @var HiddenRoute[]
     */
    protected $hiddenRoutes = [];

    private $namespace;

    public function any(string $schema, $action, ?string $name = null): Route
    {
        return $this->route(null, $schema, $action, $name);
    }

    public function get(string $schema, $action, ?string $name = null): Route
    {
        return $this->route(Request::TYPE_GET, $schema, $action, $name);
    }

    public function head(string $schema, $action, ?string $name = null): Route
    {
        return $this->route(Request::TYPE_HEAD, $schema, $action, $name);
    }

    public function post(string $schema, $action, ?string $name = null): Route
    {
        return $this->route(Request::TYPE_POST, $schema, $action, $name);
    }

    public function put(string $schema, $action, ?string $name = null): Route
    {
        return $this->route(Request::TYPE_PUT, $schema, $action, $name);
    }

    public function delete(string $schema, $action, ?string $name = null): Route
    {
        return $this->route(Request::TYPE_DELETE, $schema, $action, $name);
    }

    public function connect(string $schema, $action, ?string $name = null): Route
    {
        return $this->route(Request::TYPE_CONNECT, $schema, $action, $name);
    }

    public function options(string $schema, $action, ?string $name = null): Route
    {
        return $this->route(Request::TYPE_OPTIONS, $schema, $action, $name);
    }

    public function trace(string $schema, $action, ?string $name = null): Route
    {
        return $this->route(Request::TYPE_TRACE, $schema, $action, $name);
    }

    public function patch(string $schema, $action, ?string $name = null): Route
    {
        return $this->route(Request::TYPE_PATCH, $schema, $action, $name);
    }

    public function route($types, string $schema, $action, ?string $name = null): Route
    {
        $route = $this->newRoute($schema, $action);

        if ($types === null) {
            $types = [null];
        }

        foreach ((array)$types as $type) {
            $methodRef = &Arr::getArrayForceRef($this->routes, $type);

            Arr::set($methodRef, $name, $route);
        }

        return $route;
    }

    public function hidden(string $schema, string $name): HiddenRoute
    {
        Arr::set($this->hiddenRoutes, $name, $route = $this->newHidden($schema));

        return $route;
    }

    public function group(string $schema, ?string $prefix, callable $callable): GroupRoute
    {
        Arr::set($this->groupRoutes, $prefix, $route = $this->newGroup($schema));

        Obj::call($callable, $route);

        return $route;
    }

    public function find(string $name): ?RouteStrategy
    {
        if ($route = $this->findRoute($name)) {
            return $route;
        }

        if ($hiddenRoute = $this->findHiddenRoute($name)) {
            return $hiddenRoute;
        }

        foreach ($this->groupRoutes as $prefix => $group) {
            if (!Str::startsWith($name, $prefix)) {
                continue;
            }

            if ($route = $group->find(Str::shift($name, $prefix))) {
                return $route;
            }
        }

        return null;
    }

    public function setNamespace(string $namespace)
    {
        $this->namespace = $namespace;

        return $this;
    }

    public function getNamespace(): ?string
    {
        return $this->namespace;
    }

    protected function findRoute(string $name): ?Route
    {
        foreach ($this->routes as $routes) {
            if (isset($routes[$name])) {
                return $routes[$name];
            }
        }

        return null;
    }

    protected function findHiddenRoute(string $name): ?HiddenRoute
    {
        return $this->hiddenRoutes[$name] ?? null;
    }

    /**
     * @param $method
     *
     * @return Route[]
     */
    protected function typeRoutes(?string $method): array
    {
        $routes = Arr::getArray($this->routes, $method);

        if ($method) {
            $routes = array_merge(Arr::getArray($this->routes, ''), $routes);
        }

        return $routes;
    }

    protected function newRoute(string $schema, $action): Route
    {
        return (new Route($schema, $action))->setParent($this);
    }

    protected function newGroup(string $schema): GroupRoute
    {
        return (new GroupRoute($schema))->setParent($this);
    }

    protected function newHidden(string $schema): HiddenRoute
    {
        return (new HiddenRoute($schema))->setParent($this);
    }

    protected function routes(): array
    {
        return $this->routes;
    }
}
