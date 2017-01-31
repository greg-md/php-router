<?php

namespace Greg\Routing;

use Greg\Support\Arr;
use Greg\Support\Tools\Regex;

trait RoutingTrait
{
    private $schema;

    private $schemaInfo;

    /**
     * @var RoutesAbstract
     */
    private $parent;

    public function schema(): ?string
    {
        return $this->schema;
    }

    public function schemaInfo(): array
    {
        if ($this->schemaInfo === null) {
            $this->schemaInfo = RouteUtils::schemaInfo($this->schema);
        }

        return $this->schemaInfo;
    }

    public function setParent(RoutesAbstract $parent)
    {
        $this->parent = $parent;

        return $this;
    }

    public function getParent(): ?RoutesAbstract
    {
        return $this->parent;
    }

    public function path(array $params = []): array
    {
        [$path, $params] = $this->fetchSegment($this->schema, $params);

        if ($parent = $this->getParent() and ($parent instanceof GroupRoute)) {
            [$parentPath, $params] = $parent->path($params);

            $path = $parentPath . $path;
        }

        return [$path, $params];
    }

    protected function fetchSegment(string $schema, array $params = [], bool $required = true): array
    {
        $params = array_filter($params);

        // find all "{param}?" and "[segment]"
        if (preg_match_all($pattern = RouteUtils::schemaPattern(), $schema, $matches)) {
            // split remain string
            $parts = preg_split($pattern, $schema);

            return $this->fetchMatchedSegment($matches, $parts, $params, $required);
        }

        return [$schema, $params];
    }

    private function fetchMatchedSegment(array $matches, array $parts, array $params = [], bool $required = true): array
    {
        // start from the last
        $parts = array_reverse($parts, true);

        $path = $usedParams = [];

        $fetchFailed = false;

        foreach ($parts as $key => $remain) {
            if (array_key_exists($key, $matches[0])) {
                if ($param = $matches['param'][$key]) {
                    $param = RouteUtils::splitParam($param);

                    $usedParams[] = $param['name'];

                    if ($fetchFailed) {
                        continue;
                    }

                    $optional = $matches['paramOptional'][$key] === '?';

                    if (!$this->fetchMatchedParam($path, $param, $optional, $params, $required)) {
                        $fetchFailed = true;
                    }
                } elseif ($segment = $matches['segment'][$key]) {
                    [$segmentPath, $params] = $this->fetchSegment($segment, $params, false);

                    if ($segmentPath !== '') {
                        $path[] = $segmentPath;
                    }
                }
            }

            if ($remain !== '') {
                $path[] = $remain;
            }
        }

        Arr::remove($params, $usedParams);

        if ($fetchFailed) {
            return ['', $params];
        }

        $path = implode('', array_reverse($path));

        return [$path, $params];
    }

    private function fetchMatchedParam(array &$path, array $param, bool $optional, array $params, bool $required = true): bool
    {
        $name = $param['name'];

        $value = Arr::has($params, $name) ? $this->bindOutParam($name, $params[$name]) : $param['default'];

        $value = (string) $value;

        if ($value === '') {
            if ($optional) {
                return true;
            }

            if ($required) {
                throw new RoutingException('Parameter `' . $param['name'] . '` is required in the route.');
            }

            return false;
        }

        if ($param['type'] and $value !== (string) RouteUtils::paramFixType($value, $param['type'])) {
            throw new RoutingException('Parameter `' . $name . '` is not `' . $param['type'] . '`.');
        }

        if ($param['regex'] and !preg_match(Regex::pattern('^' . $param['regex'] . '$'), $value)) {
            throw new RoutingException('Parameter `' . $name . '` is not `' . $param['regex'] . '`.');
        }

        if (!$optional or $path or $value !== $param['default']) {
            $path[] = $value;
        }

        return true;
    }

    protected function fetchMatchedParams(array $regexParams, array $matches): array
    {
        $cleanParams = RouteUtils::fetchMatchedParams($regexParams, $matches);

        $params = [];

        foreach ($cleanParams as $name => $value) {
            $params[$name] = $value !== null ? $this->bindInParam($name, $value) : $value;
        }

        return [$cleanParams, $params];
    }
}
