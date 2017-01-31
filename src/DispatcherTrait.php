<?php

namespace Greg\Routing;

trait DispatcherTrait
{
    private $dispatcher;

    private $ioc;

    public function setDispatcher(callable $callable)
    {
        $this->dispatcher = $callable;

        return $this;
    }

    public function getDispatcher(): ?callable
    {
        return $this->dispatcher;
    }

    public function setIoc(callable $ioc)
    {
        $this->ioc = $ioc;

        return $this;
    }

    public function getIoc(): ?callable
    {
        return $this->ioc;
    }
}
