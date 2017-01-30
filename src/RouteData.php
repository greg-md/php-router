<?php

namespace Greg\Routing;

use Greg\Support\Arr;

class RouteData
{
    private $path;

    private $params = [];

    private $cleanParams = [];

    public function __construct(string $path, array $params, array $cleanParams)
    {
        $this->path = $path;

        $this->params = $params;

        $this->cleanParams = $cleanParams;
    }

    public function path(): string
    {
        return $this->path;
    }

    public function params(): array
    {
        return $this->params;
    }

    public function cleanParams($key = null)
    {
        return func_num_args() ? Arr::get($this->cleanParams, $key) : $this->cleanParams;
    }
}