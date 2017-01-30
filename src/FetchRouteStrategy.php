<?php

namespace Greg\Routing;

interface FetchRouteStrategy
{
    public function fetch(array $params = []): string;
}