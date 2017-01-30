<?php

namespace Greg\Routing;

use Greg\Routing\Bind\BindInOutStrategy;
use Greg\Routing\Bind\BindInStrategy;
use Greg\Routing\Bind\BindInTrait;
use Greg\Routing\Bind\BindOutTrait;
use Greg\Support\Arr;
use Greg\Support\Http\Request;
use Greg\Support\Obj;
use Greg\Support\Str;

abstract class RoutesAbstract
{
    use BindInTrait, BindOutTrait, ErrorActionTrait, DispatcherTrait, HostTrait;

    /**
     * @var RequestRoute[][]
     */
    protected $requestRoutes = [];

    /**
     * @var GroupRoute[]
     */
    protected $groupRoutes = [];

    /**
     * @var HiddenRoute[]
     */
    protected $hiddenRoutes = [];

    public function any(string $schema, $action, ?string $name = null): RequestRoute
    {
        return $this->request($schema, $action, $name);
    }

    public function get(string $schema, $action, ?string $name = null): RequestRoute
    {
        return $this->request($schema, $action, $name, Request::TYPE_GET);
    }

    public function head(string $schema, $action, ?string $name = null): RequestRoute
    {
        return $this->request($schema, $action, $name, Request::TYPE_HEAD);
    }

    public function post(string $schema, $action, ?string $name = null): RequestRoute
    {
        return $this->request($schema, $action, $name, Request::TYPE_POST);
    }

    public function put(string $schema, $action, ?string $name = null): RequestRoute
    {
        return $this->request($schema, $action, $name, Request::TYPE_PUT);
    }

    public function delete(string $schema, $action, ?string $name = null): RequestRoute
    {
        return $this->request($schema, $action, $name, Request::TYPE_DELETE);
    }

    public function connect(string $schema, $action, ?string $name = null): RequestRoute
    {
        return $this->request($schema, $action, $name, Request::TYPE_CONNECT);
    }

    public function options(string $schema, $action, ?string $name = null): RequestRoute
    {
        return $this->request($schema, $action, $name, Request::TYPE_OPTIONS);
    }

    public function trace(string $schema, $action, ?string $name = null): RequestRoute
    {
        return $this->request($schema, $action, $name, Request::TYPE_TRACE);
    }

    public function patch(string $schema, $action, ?string $name = null): RequestRoute
    {
        return $this->request($schema, $action, $name, Request::TYPE_PATCH);
    }

    public function request(string $schema, $action, ?string $name = null, ?string $method = null): RequestRoute
    {
        $methodRef = &Arr::getArrayForceRef($this->requestRoutes, $method);

        Arr::set($methodRef, $name, $route = $this->newRequest($schema, $action));

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

    public function bind(string $name, BindInOutStrategy $strategy)
    {
        $this->bindIn($name, $strategy);

        $this->bindOut($name, $strategy);

        return $this;
    }

    public function bindCallable($name, callable $callableIn, ?callable $callableOut = null)
    {
        if ($callableOut) {
            return $this->bind($name, new class($callableIn, $callableOut) implements BindInOutStrategy {
                private $callableIn;

                private $callableOut;

                function __construct(callable $callableIn, callable $callableOut)
                {
                    $this->callableIn = $callableIn;

                    $this->callableOut = $callableOut;
                }

                public function input($value)
                {
                    return Obj::call($this->callableIn, $value);
                }

                public function output($value)
                {
                    return Obj::call($this->callableOut, $value);
                }
            });
        }

        return $this->bindIn($name, new class($callableIn) implements BindInStrategy {
            private $callableIn;

            function __construct(callable $callableIn, callable $callableOut)
            {
                $this->callableIn = $callableIn;
            }

            public function input($value)
            {
                return Obj::call($this->callableIn, $value);
            }
        });
    }

    public function dispatch(string $path, ?string $method = null): string
    {
        foreach ($this->requestTypeRoutes($method) as $route) {
            if ($request = $route->match($path)) {
                return $route->exec($request);
            }
        }

        foreach ($this->groupRoutes as $group) {
            if ($matched = $group->match($path, $method)) {
                [$route, $request] = $matched;

                return $route->exec($request);
            }
        }

        throw new RoutingException('Route for path `' . $path . '` not found.');
    }

    public function findRoute(string $name): ?FetchRouteStrategy
    {
        if ($requestRoute = $this->findRequestRoute($name)) {
            return $requestRoute;
        }

        if ($hiddenRoute = $this->findHiddenRoute($name)) {
            return $hiddenRoute;
        }

        foreach ($this->groupRoutes as $prefix => $group) {
            if (!Str::startsWith($name, $prefix)) {
                continue;
            }

            if ($route = $group->findRoute(Str::shift($name, $prefix))) {
                return $route;
            }
        }

        return null;
    }

    protected function getRoute(string $name): FetchRouteStrategy
    {
        if ($route = $this->findRoute($name)) {
            return $route;
        }

        throw new RoutingException('Route `' . $name . '` not found.');
    }

    protected function findRequestRoute(string $name): ?RequestRoute
    {
        foreach ($this->requestRoutes as $routes) {
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
     * @return RequestRoute[]
     */
    protected function requestTypeRoutes(?string $method): array
    {
        $routes = Arr::getArray($this->requestRoutes, $method);

        if ($method) {
            $routes = array_merge(Arr::getArray($this->requestRoutes, ''), $routes);
        }

        return $routes;
    }

    protected function newRequest(string $schema, $action): RequestRoute
    {
        return (new RequestRoute($schema, $action))->setParent($this);
    }

    protected function newGroup(string $schema): GroupRoute
    {
        return (new GroupRoute($schema))->setParent($this);
    }

    protected function newHidden(string $schema): HiddenRoute
    {
        return (new HiddenRoute($schema))->setParent($this);
    }

    protected function requestRoutes(): array
    {
        return $this->requestRoutes;
    }
}
