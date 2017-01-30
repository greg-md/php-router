<?php

namespace Greg\Routing;

use Greg\Routing\Bind\BindOutTrait;

class HiddenRoute implements FetchRouteStrategy
{
    use RouteTrait, BindOutTrait, HostTrait, FetchRouteTrait;

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
