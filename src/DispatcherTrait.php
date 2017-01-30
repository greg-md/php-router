<?php

namespace Greg\Routing;

trait DispatcherTrait
{
    private $dispatcher;

    public function setDispatcher(callable $callable)
    {
        $this->dispatcher = $callable;

        return $this;
    }

    public function getDispatcher(): ?callable
    {
        return $this->dispatcher;
    }
}