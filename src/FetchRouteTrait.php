<?php

namespace Greg\Routing;

use Greg\Support\Url;

trait FetchRouteTrait
{
    public function fetch(array $params = []): string
    {
        [$path, $params] = $this->path($params);

        if (!$path) {
            $path = '/';
        }

        if ($host = $this->getHost()) {
            [$host, $params] = $this->fetchSegment($host, $params);

            $path = Url::schemly($host . $path);
        }

        if ($params) {
            $path .= '?' . http_build_query($params);
        }

        return $path;
    }

    abstract public function path(array $params = []): array;

    abstract protected function fetchSegment(string $schema, array $params = [], bool $required = true): array;

    abstract public function getHost(): ?string;
}
