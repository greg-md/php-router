<?php

namespace Greg\Router;

use Greg\Support\Arr;

trait RouterTrait
{
    /**
     * @var Route[]
     */
    protected $routes = [];

    protected $bindersIn = [];

    protected $boundInParams = [];

    protected $bindersOut = [];

    protected $boundOutParams = [];

    protected $errorAction = null;

    public function any($format, $action, array $settings = [])
    {
        $settings['type'] = null;

        return $this->setRoute($format, $action, $settings);
    }

    public function post($format, $action, array $settings = [])
    {
        $settings['type'] = Route::TYPE_POST;

        return $this->setRoute($format, $action, $settings);
    }

    public function get($format, $action, $settings = [])
    {
        if (is_scalar($settings)) {
            $settings = ['name' => $settings];
        }

        $settings['type'] = Route::TYPE_GET;

        return $this->setRoute($format, $action, $settings);
    }

    public function hidden($format, $name, array $settings = [])
    {
        $settings['name'] = $name;

        $settings['type'] = Route::TYPE_HIDDEN;

        return $this->setRoute($format, null, $settings);
    }

    public function group($format, callable $callable, array $settings = [])
    {
        $settings['type'] = Route::TYPE_GROUP;

        $route = $this->setRoute($format, null, $settings);

        $route->strict(false);

        $this->callCallableWith($callable, $route);

        return $route;
    }

    public function setRoute($format, $action, array $settings = null)
    {
        $this->routes[] = $route = $this->createRoute($format, $action, $settings);

        return $route;
    }

    public function findRoute($name)
    {
        foreach($this->routes as $route) {
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
        return (bool)$this->routes;
    }

    public function createRoute($format, $action, array $settings = [])
    {
        return $this->_createRoute($format, $action, $settings);
    }

    protected function _createRoute($format, $action, array $settings = [])
    {
        return $this->newRoute($format, $action, $settings);
    }

    protected function newRoute($format, $action, array $settings = [])
    {
        return new Route($format, $action, $settings);
    }

    public function dispatchPath($path, &$foundRoute = null)
    {
        foreach($this->routes as $route) {
            if ($matchedRoute = $route->match($path)) {
                $foundRoute = $matchedRoute;

                return $matchedRoute->dispatch();
            }
        }

        return null;
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
            $value = is_callable($binder) ? $this->callCallable($binder, $params) : $binder;

            $this->boundOutParams[$name] = $value;
        } else {
            $value = Arr::get($params, $name);
        }

        return $value;
    }

    public function bindOutParams(array $params)
    {
        foreach($params as $name => &$value) {
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
            $value = $this->callCallable($binder, $params);

            $this->boundInParams[$name] = $value;
        } else {
            $value = Arr::get($params, $name);
        }

        return $value;
    }

    public function bindInParams(array $params)
    {
        foreach($params as $name => &$value) {
            $value = $this->getBoundInParam($name, $params);
        }
        unset($value);

        return $params;
    }

    public function setErrorAction($action)
    {
        $this->errorAction = $action;

        return $this;
    }

    public function getErrorAction()
    {
        return $this->errorAction;
    }

    abstract protected function callCallable(callable $callable, ...$args);

    abstract protected function callCallableWith(callable $callable, ...$args);
}