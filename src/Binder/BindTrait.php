<?php

namespace Greg\Routing\Binder;

trait BindTrait
{
    use BindInTrait, BindOutTrait;

    public function bind(string $name, callable $callableIn, ?callable $callableOut = null)
    {
        $this->bindIn($name, $callableIn);

        if ($callableOut) {
            $this->bindOut($name, $callableOut);
        }

        return $this;
    }

    public function bindStrategy(string $name, BindInOutStrategy $strategy)
    {
        $this->bindInStrategy($name, $strategy);

        $this->bindOutStrategy($name, $strategy);

        return $this;
    }
}
