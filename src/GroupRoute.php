<?php

namespace Greg\Routing;

use Greg\Routing\Bind\BindInStrategy;
use Greg\Routing\Bind\BindOutStrategy;
use Greg\Support\Tools\Regex;

class GroupRoute extends RoutesAbstract
{
    use RouteTrait;

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

    public function match(string $path, ?string $method = null): ?array
    {
        [$regex, $regexParams] = $this->schemaInfo();

        if (preg_match(Regex::pattern('^' . $regex . '(?\'child\'.*)'), $path, $matches)) {
            if (!$matched = $this->matchChild($matches['child'], $method)) {
                return null;
            }

            /* @var RequestRoute $route */
            /* @var RouteData $data */
            [$route, $data] = $matched;

            [$cleanParams, $params] = $this->fetchMatchedParams($regexParams, $matches);

            $data = new RouteData($path, $data->params() + $params, $data->cleanParams() + $cleanParams);

            return [$route, $data];
        }

        return null;
    }

    protected function matchChild(string $path, ?string $method = null): ?array
    {
        foreach ($this->requestTypeRoutes($method) as $route) {
            if ($data = $route->match($path)) {
                return [$route, $data];
            }
        }

        foreach ($this->groupRoutes as $group) {
            if ($matched = $group->match($path, $method)) {
                return $matched;
            }
        }

        return null;
    }
}
