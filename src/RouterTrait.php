<?php

namespace Greg\Router;

use Greg\Support\Arr;
use Greg\Support\Http\Request;
use Greg\Support\Obj;

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

    protected $dispatcher = null;

    public function any($format, $action = null)
    {
        return $this->setRoute($format, $action);
    }

    public function get($format, $action = null)
    {
        return $this->setRoute($format, $action)->setType(Request::TYPE_GET);
    }

    public function head($format, $action = null)
    {
        return $this->setRoute($format, $action)->setType(Request::TYPE_HEAD);
    }

    public function post($format, $action = null)
    {
        return $this->setRoute($format, $action)->setType(Request::TYPE_POST);
    }

    public function put($format, $action = null)
    {
        return $this->setRoute($format, $action)->setType(Request::TYPE_PUT);
    }

    public function delete($format, $action = null)
    {
        return $this->setRoute($format, $action)->setType(Request::TYPE_DELETE);
    }

    public function connect($format, $action = null)
    {
        return $this->setRoute($format, $action)->setType(Request::TYPE_CONNECT);
    }

    public function options($format, $action = null)
    {
        return $this->setRoute($format, $action)->setType(Request::TYPE_OPTIONS);
    }

    public function trace($format, $action = null)
    {
        return $this->setRoute($format, $action)->setType(Request::TYPE_TRACE);
    }

    public function patch($format, $action = null)
    {
        return $this->setRoute($format, $action)->setType(Request::TYPE_PATCH);
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

        Obj::callCallableWith($callable, $route);

        return $route;
    }

    public function setRoute($format, $action = null)
    {
        $this->routes[] = $route = $this->createRoute($format, $action);

        return $route;
    }

    public function createRoute($format, $action = null)
    {
        return new Route($format, $action);
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

    public function hasBoundOut($name)
    {
        return array_key_exists($name, $this->bindersOut);
    }

    public function getBoundOut($name)
    {
        return $this->hasBoundOut($name) ? $this->bindersOut[$name] : null;
    }

    public function getBoundOutParam($name, array $params = [])
    {
        if (array_key_exists($name, $this->boundOutParams)) {
            return $this->boundOutParams[$name];
        }

        if ($binder = $this->getBoundOut($name)) {
            $value = is_callable($binder) ? Obj::callCallableWith($binder, $params) : $binder;

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

    public function hasBoundIn($name)
    {
        return array_key_exists($name, $this->bindersIn);
    }

    public function getBoundIn($name)
    {
        return $this->hasBoundIn($name) ? $this->bindersIn[$name] : null;
    }

    public function getBoundInParam($name, array $params = [])
    {
        if (array_key_exists($name, $this->boundInParams)) {
            return $this->boundInParams[$name];
        }

        if ($binder = $this->getBoundIn($name)) {
            $value = Obj::callCallableWith($binder, $params);

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

    public function dispatchAction($action, array $params = [])
    {
        if (!is_callable($action)) {
            if ($dispatcher = $this->getNearestDispatcher()) {
                $action = Obj::callCallableWith($dispatcher, $action);
            }
        }

        if (!$action) {
            throw new \Exception('Route action is undefined.');
        }

        if (!is_callable($action)) {
            throw new \Exception('Route action is not callable.');
        }

        $request = new Request($params);

        return Obj::callCallableWith($action, $this, $request, ...array_values($params));
    }

    public function dispatchException(\Exception $e)
    {
        if ($errorAction = $this->getErrorAction()) {
            return $this->dispatchAction($errorAction, ['exception' => $e]);
        }

        throw $e;
    }

    protected function getNearestDispatcher()
    {
        if ($dispatcher = $this->getDispatcher()) {
            return $dispatcher;
        }

        return null;
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

    public function setDispatcher(callable $callable)
    {
        $this->dispatcher = $callable;

        return $this;
    }

    public function getDispatcher()
    {
        return $this->dispatcher;
    }
}
