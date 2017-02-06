<?php

namespace Greg\Routing;

use Greg\Support\Tools\Regex;

trait PatternsTrait
{
    private $patterns = [];

    public function pattern(string $name, string $regex)
    {
        $this->patterns[$name] = Regex::disableGroups($regex);

        return $this;
    }

    public function type(string $name, string $type)
    {
        $this->patterns[$name] = RouteUtils::paramRegexType($type);

        return $this;
    }

    public function getPattern(string $name): ?string
    {
        return $this->patterns[$name] ?? null;
    }
}