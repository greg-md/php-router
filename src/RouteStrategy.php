<?php

namespace Greg\Routing;

interface RouteStrategy
{
    public function url(array $params = []): string;
}
