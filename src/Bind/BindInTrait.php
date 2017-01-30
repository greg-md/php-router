<?php

namespace Greg\Routing\Bind;

trait BindInTrait
{
    /**
     * @var BindInStrategy[]
     */
    private $bindersIn = [];

    public function bindIn(string $name, BindInStrategy $strategy)
    {
        $this->bindersIn[$name] = $strategy;

        return $this;
    }

    public function binderIn(string $name): ?BindInStrategy
    {
        return $this->bindersIn[$name] ?? null;
    }

    public function bindInParam($name, $value)
    {
        if ($binder = $this->binderIn($name)) {
            return $binder->input($value);
        }

        return $value;
    }
}