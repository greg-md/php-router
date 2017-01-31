<?php

namespace Greg\Routing\Bind;

use Greg\Support\Obj;

trait BindInTrait
{
    /**
     * @var BindInStrategy[]|callable[]
     */
    private $bindersIn = [];

    public function bindIn(string $name, callable $callable)
    {
        $this->bindersIn[$name] = $callable;

        return $this;
    }

    public function bindInStrategy(string $name, BindInStrategy $strategy)
    {
        $this->bindersIn[$name] = $strategy;

        return $this;
    }

    /**
     * @param string $name
     * @return BindInStrategy|callable
     */
    public function binderIn(string $name)
    {
        return $this->bindersIn[$name] ?? null;
    }

    public function bindInParam(string $name, $value)
    {
        if ($binder = $this->binderIn($name)) {
            if ($binder instanceof BindInStrategy) {
                return $binder->input($value);
            }

            return Obj::call($binder, $value);
        }

        return $value;
    }
}
