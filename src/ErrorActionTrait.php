<?php

namespace Greg\Routing;

trait ErrorActionTrait
{
    private $errorAction;

    public function setErrorAction($action)
    {
        $this->errorAction = $action;

        return $this;
    }

    public function getErrorAction()
    {
        return $this->errorAction;
    }
}