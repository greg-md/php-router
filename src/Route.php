<?php

namespace Greg\Routing;

use Greg\Routing\Binder\BindInStrategy;
use Greg\Routing\Binder\BindOutStrategy;
use Greg\Routing\Binder\BindTrait;
use Greg\Support\Obj;
use Greg\Support\Tools\Regex;

class Route implements RouteStrategy
{
    use RoutingTrait, BindTrait, RouteTrait, ErrorActionTrait, DispatcherTrait, HostTrait, PatternsTrait;

    private $action;

    public function __construct(string $schema, $action)
    {
        $this->schema = $schema;

        $this->action = $action;

        return $this;
    }

    public function where(string $name, string $regex)
    {
        return $this->pattern($name, $regex);
    }

    public function whereIs(string $name, string $type)
    {
        return $this->type($name, $type);
    }

    public function getPattern(string $name): ?string
    {
        return $this->patterns[$name] ?? ($this->getParent() ? $this->getParent()->getPattern($name) : null);
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

    public function exec(RouteData $data): string
    {
        try {
            $result = $this->execAction($this->action, $data);

            return $result;
        } catch (\Exception $e) {
            return $this->execErrorAction($e, $data);
        }
    }

    protected function execAction($action, RouteData $data, ...$params): string
    {
        if (!is_callable($action = $this->fetchAction($action))) {
            throw new RoutingException('Route action is not a callable.');
        }

        return Obj::callMixed($action, $data, ...array_values($data->params()), ...$params);
    }

    protected function execErrorAction(\Exception $e, RouteData $data): string
    {
        if ($errorAction = $this->getErrorAction()) {
            return $this->execAction($errorAction, $data, $e);
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
