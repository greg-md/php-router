<?php

namespace Greg\Router;

use Greg\Support\Arr;
use Greg\Support\IoC\IoCManagerAccessorTrait;
use Greg\Support\Obj;

trait RouterTrait
{
    use IoCManagerAccessorTrait;

    /**
     * @var Route[]
     */
    protected $routes = [];

    protected $bindersIn = [];

    protected $boundInParams = [];

    protected $bindersOut = [];

    protected $boundOutParams = [];

    protected $errorAction = null;

    public function any($format, callable $action = null)
    {
        return $this->setRoute($format, $action);
    }

    public function post($format, callable $action = null)
    {
        return $this->setRoute($format, $action)->setType(Route::TYPE_POST);
    }

    public function get($format, callable $action = null)
    {
        return $this->setRoute($format, $action)->setType(Route::TYPE_GET);
    }

    public function hidden($format, $name)
    {
        return $this->setRoute($format)->setType(Route::TYPE_HIDDEN)->setName($name);
    }

    public function group($format, callable $callable)
    {
        $route = $this->setRoute($format);

        $route->setType(Route::TYPE_GROUP);

        $route->strict(false);

        if ($ioc = $this->getIoCManager()) {
            $ioc->callCallableWith($callable, $route);
        } else {
            Obj::callCallableWith($callable, $route);
        }

        return $route;
    }

    public function setRoute($format, callable $action = null)
    {
        $this->routes[] = $route = $this->createRoute($format, $action);

        return $route;
    }

    public function createRoute($format, callable $action = null)
    {
        return new Route($format, $action, $this->getIoCManager());
    }

    public function findRoute($name)
    {
        foreach ($this->routes as $route) {
            if ($routeName = $route->getName() and $routeName == $name) {
                return $route;
            }

            if ($route->hasRoutes() and $subRoute = $route->findRoute($name)) {
                return $subRoute;
            }
        }

        return null;
    }

    public function hasRoute($name)
    {
        return $this->findRoute($name) ? true : false;
    }

    public function getRoute($name)
    {
        if (!$route = $this->findRoute($name)) {
            throw new \Exception('Route `' . $name . '` not found.');
        }

        return $route;
    }

    public function getRoutes()
    {
        return $this->routes;
    }

    public function hasRoutes()
    {
        return (bool) $this->routes;
    }

    public function findRouteByPath($path)
    {
        foreach ($this->routes as $route) {
            if ($matchedRoute = $route->match($path)) {
                return $matchedRoute;
            }
        }

        return null;
    }

    public function dispatchPath($path)
    {
        return $this->findRouteByPath($path)->dispatch();
    }

    public function fetchRoute($routeName, array $params = [], $full = false)
    {
        return $this->getRoute($routeName)->fetch($params, $full);
    }

    public function bindOut($name, $result)
    {
        $this->bindersOut[$name] = $result;

        return $this;
    }

    protected function hasBoundOut($name)
    {
        return array_key_exists($name, $this->bindersOut);
    }

    protected function getBoundOut($name)
    {
        return $this->hasBoundOut($name) ? $this->bindersOut[$name] : null;
    }

    public function getBoundOutParam($name, array $params = [])
    {
        if (array_key_exists($name, $this->boundOutParams)) {
            return $this->boundOutParams[$name];
        }

        if ($binder = $this->getBoundOut($name)) {
            $value = is_callable($binder) ? call_user_func_array($binder, [$params]) : $binder;

            $this->boundOutParams[$name] = $value;
        } else {
            $value = Arr::get($params, $name);
        }

        return $value;
    }

    public function bindOutParams(array $params)
    {
        foreach ($params as $name => &$value) {
            $value = $this->getBoundOutParam($name, $params);
        }
        unset($value);

        return $params;
    }

    public function bindIn($name, callable $result)
    {
        $this->bindersIn[$name] = $result;

        return $this;
    }

    protected function hasBoundIn($name)
    {
        return array_key_exists($name, $this->bindersIn);
    }

    protected function getBoundIn($name)
    {
        return $this->hasBoundIn($name) ? $this->bindersIn[$name] : null;
    }

    public function getBoundInParam($name, array $params = [])
    {
        if (array_key_exists($name, $this->boundInParams)) {
            return $this->boundInParams[$name];
        }

        if ($binder = $this->getBoundIn($name)) {
            $value = call_user_func_array($binder, [$params]);

            $this->boundInParams[$name] = $value;
        } else {
            $value = Arr::get($params, $name);
        }

        return $value;
    }

    public function bindInParams(array $params)
    {
        foreach ($params as $name => &$value) {
            $value = $this->getBoundInParam($name, $params);
        }
        unset($value);

        return $params;
    }

    public function setErrorAction(callable $action)
    {
        $this->errorAction = $action;

        return $this;
    }

    public function getErrorAction()
    {
        return $this->errorAction;
    }
}
