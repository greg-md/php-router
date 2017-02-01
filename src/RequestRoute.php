<?php

namespace Greg\Routing;

use Greg\Routing\Bind\BindInStrategy;
use Greg\Routing\Bind\BindInTrait;
use Greg\Routing\Bind\BindOutStrategy;
use Greg\Routing\Bind\BindOutTrait;
use Greg\Support\Obj;
use Greg\Support\Tools\Regex;

class RequestRoute implements RouteStrategy
{
    use RoutingTrait, BindInTrait, BindOutTrait, RouteTrait, ErrorActionTrait, DispatcherTrait, HostTrait;

    private $action;

    public function __construct(string $schema, $action)
    {
        $this->schema = $schema;

        $this->action = $action;

        return $this;
    }

    public function binderIn(string $name): ?BindInStrategy
    {
        return $this->bindersIn[$name] ?? ($this->getParent() ? $this->getParent()->binderIn($name) : null);
    }

    public function binderOut(string $name): ?BindOutStrategy
    {
        return $this->bindersOut[$name] ?? ($this->getParent() ? $this->getParent()->binderOut($name) : null);
    }

    public function getErrorAction()
    {
        return $this->errorAction ?: ($this->getParent() ? $this->getParent()->getErrorAction() : null);
    }

    public function getDispatcher(): ?callable
    {
        return $this->dispatcher ?: ($this->getParent() ? $this->getParent()->getDispatcher() : null);
    }

    public function getHost(): ?string
    {
        return $this->host ?: ($this->getParent() ? $this->getParent()->getHost() : null);
    }

    public function match(string $path, RouteData &$data = null): bool
    {
        $info = $this->schemaInfo();

        if (preg_match(Regex::pattern('^' . $info['regex'] . '$'), $path, $matches)) {
            [$cleanParams, $params] = $this->fetchMatchedParams($info['params'], $matches);

            $data = new RouteData($path, $params, $cleanParams);

            return true;
        }

        return false;
    }

    public function exec(RouteData $request): string
    {
        try {
            $result = $this->execAction($this->action, $request);

            return $result;
        } catch (\Exception $e) {
            return $this->execErrorAction($e, $request);
        }
    }

    protected function execAction($action, RouteData $request, ...$params): string
    {
        if (!is_callable($action = $this->fetchAction($action))) {
            throw new RoutingException('Route action is not a callable.');
        }

        return Obj::callMixed($action, $request, ...array_values($request->params()), ...$params);
    }

    protected function execErrorAction(\Exception $e, RouteData $request): string
    {
        if ($errorAction = $this->getErrorAction()) {
            return $this->execAction($errorAction, $request, $e);
        }

        throw $e;
    }

    protected function fetchAction($action)
    {
        if ($dispatcher = $this->getDispatcher()) {
            $action = Obj::call($dispatcher, $action);
        }

        if (is_scalar($action)) {
            if (strpos($action, '@') !== false) {
                [$controllerName, $actionName] = explode('@', $action, 2);

                $controller = $this->fetchController($controllerName);

                if (!method_exists($controller, $actionName)) {
                    throw new \Exception('Action `' . $actionName . '` does not exists in ' . $controllerName . '` controller.');
                }

                $action = [$controller, $actionName];
            }
        }

        return $action;
    }

    protected function fetchController($controllerName)
    {
        if ($parent = $this->getParent()) {
            $controllerName = $parent->getNamespace() . '\\' . $controllerName;
        }

        if ($ioc = $this->getIoc()) {
            $controller = Obj::call($ioc, $controllerName);

            if (!is_object($controller)) {
                throw new \Exception('Controller `' . $controllerName . '` could not be instantiated.');
            }

            return $controller;
        }

        return new $controllerName();
    }
}
