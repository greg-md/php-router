<?php

namespace Greg\Routing;

trait HostTrait
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
