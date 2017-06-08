<?php

namespace Greg\Routing\Binder;

trait BindOutTrait
{
    /**
     * @var BindOutStrategy[]|callable[]
     */
    private $bindersOut = [];

    public function bindOut(string $name, callable $callable)
    {
        $this->bindersOut[$name] = $callable;

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

            return call_user_func_array($binder, [$value]);
        }

        return $value;
    }
}
