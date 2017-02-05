<?php

namespace Greg\Routing\Binder;

use Greg\Support\Obj;

trait BindOutTrait
{
    /**
     * @var BindOutStrategy[]|callable[]
     */
    private $bindersOut = [];

    public function bindOut(string $name, callable $strategy)
    {
        $this->bindersOut[$name] = $strategy;

        return $this;
    }

    public function bindOutStrategy(string $name, BindOutStrategy $strategy)
    {
        $this->bindersOut[$name] = $strategy;

        return $this;
    }

    /**
     * @param string $name
     *
     * @return BindOutStrategy|callable
     */
    public function binderOut(string $name)
    {
        return $this->bindersOut[$name] ?? null;
    }

    public function bindOutParam(string $name, $value)
    {
        if ($binder = $this->binderOut($name)) {
            if ($binder instanceof BindOutStrategy) {
                return $binder->output($value);
            }

            return Obj::call($binder, $value);
        }

        return $value;
    }
}
