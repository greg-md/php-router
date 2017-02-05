<?php

namespace Greg\Routing;

use Greg\Routing\Binder\BindInStrategy;
use Greg\Routing\Binder\BindOutStrategy;
use Greg\Support\Tools\Regex;

class GroupRoute extends RoutesAbstract
{
    use RoutingTrait;

    public function __construct(string $schema)
    {
        $this->schema = $schema;

        return $this;
    }

    public function binderIn(string $name): ?BindInStrategy
    {
        return parent::binderIn($name) ?: ($this->getParent() ? $this->getParent()->binderIn($name) : null);
    }

    public function binderOut(string $name): ?BindOutStrategy
    {
        return parent::binderOut($name) ?: ($this->getParent() ? $this->getParent()->binderOut($name) : null);
    }

    public function getErrorAction()
    {
        return parent::getErrorAction() ?: ($this->getParent() ? $this->getParent()->getErrorAction() : null);
    }

    public function getHost(): ?string
    {
        return parent::getHost() ?: ($this->getParent() ? $this->getParent()->getHost() : null);
    }

    public function getDispatcher(): ?callable
    {
        return parent::getDispatcher() ?: ($this->getParent() ? $this->getParent()->getDispatcher() : null);
    }

    public function getNamespace(): ?string
    {
        $namespace = ($this->getParent() ? $this->getParent()->getNamespace() : null);

        return $namespace . '\\' . parent::getNamespace();
    }

    public function match(string $path, ?string $method = null, RouteStrategy &$route = null, RouteData &$data = null): bool
    {
        $info = $this->schemaInfo();

        if (preg_match(Regex::pattern('^' . $info['regex'] . '(?\'child\'.*)'), $path, $matches)) {
            if (!$this->matchChild($matches['child'], $method, $route, $data)) {
                return false;
            }
            [$cleanParams, $params] = $this->fetchMatchedParams($info['params'], $matches);

            $data = new RouteData($path, $data->params() + $params, $data->cleanParams() + $cleanParams);

            return true;
        }

        return false;
    }

    protected function matchChild(string $path, ?string $method = null, RouteStrategy &$route = null, RouteData &$data = null): bool
    {
        foreach ($this->requestTypeRoutes($method) as $route) {
            if ($route->match($path, $data)) {
                return true;
            }
        }

        foreach ($this->groupRoutes as $group) {
            if ($group->match($path, $method, $route, $data)) {
                return true;
            }
        }

        return false;
    }
}
