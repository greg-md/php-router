<?php

namespace Greg\Routing;

use Greg\Routing\Binder\BindOutTrait;

class HiddenRoute implements RouteStrategy
{
    use RoutingTrait, BindOutTrait, HostTrait, RouteTrait;

    public function __construct(string $schema)
    {
        $this->schema = $schema;

        return $this;
    }

    public function binderOut(string $name)
    {
        return $this->bindersOut[$name] ?? ($this->getParent() ? $this->getParent()->binderOut($name) : null);
    }

    public function getHost(): ?string
    {
        return $this->host ?: ($this->getParent() ? $this->getParent()->getHost() : null);
    }
}
