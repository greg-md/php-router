<?php

namespace Greg\Routing\Bind;

trait BindOutTrait
{
    /**
     * @var BindOutStrategy[]
     */
    private $bindersOut = [];

    public function bindOut(string $name, BindOutStrategy $strategy)
    {
        $this->bindersOut[$name] = $strategy;

        return $this;
    }

    public function binderOut(string $name): ?BindOutStrategy
    {
        return $this->bindersOut[$name] ?? null;
    }

    public function bindOutParam($name, $value)
    {
        if ($binder = $this->binderOut($name)) {
            return $binder->output($value);
        }

        return $value;
    }
}