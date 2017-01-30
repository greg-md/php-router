<?php

namespace Greg\Routing;

abstract class RoutingAbstract
{
    private $host;

    public function setHost(string $name)
    {
        $this->host = $name;

        return $this;
    }

    public function getHost(): ?string
    {
        return $this->host;
    }
}
