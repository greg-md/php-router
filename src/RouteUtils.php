<?php

namespace Greg\Routing;

use Greg\Support\Str;
use Greg\Support\Tools\InNamespaceRegex;
use Greg\Support\Tools\Regex;

class RouteUtils
{
    /**
     * Regex pattern for param:default|regex.
     *
     * @return string
     */
    public static function paramPattern(): string
    {
        return Regex::pattern(static::paramRegex());
    }

    public static function paramRegex(): string
    {
        static $regex;

        if (!$regex) {
            $namePattern = '(?\'name\'(?:\\\\\\:|\\\\\\#|\\\\\\||[^\\:\\#\\|])+?)';

            $defaultPattern = '(?:\:(?\'default\'(?:\\\\\\#|\\\\\\||[^\\#\\|])+?))?';

            $typePattern = '(?:\#(?\'type\'(?:\\\\\\||[^\\|]).+?))?';

            $regexPattern = '(?:\|(?\'regex\'.+?))?';

            $regex = '^' . $namePattern . $defaultPattern . $typePattern . $regexPattern . '$';
        }

        return $regex;
    }

    public static function schemaPattern(): string
    {
        return Regex::pattern(static::schemaRegex());
    }

    // find all "{param}?" and "[segment[sub-segment]]"
    public static function schemaRegex(): string
    {
        static $regex;

        if (!$regex) {
            $curlyBrR = (new InNamespaceRegex('{', '}'))->capture('param');

            $squareBrR = (new InNamespaceRegex('[', ']'))->recursive()->capture('segment');

            $regex = "(?:{$curlyBrR}(?'paramOptional'\\?)?)|(?:{$squareBrR})";
        }

        return $regex;
    }

    public static function schemaInfo(string $schema): array
    {
        // find all "{param}?" and "[segment]?"
        if (preg_match_all($pattern = static::schemaPattern(), $schema, $matches)) {
            // split schema by its pattern to get static segments
            $segments = preg_split($pattern, $schema);

            return self::schemaMatchedInfo($matches, $segments);
        }

        return [Regex::quote($schema), []];
    }

    public static function splitParam(string $param): array
    {
        preg_match(static::paramPattern(), $param, $matches);

        $name = $matches['name'] ?? $param;

        $default = $matches['default'] ?? null;

        $type = $matches['type'] ?? null;

        if ($regex = $matches['regex'] ?? null) {
            $regex = self::paramFetchRegex($regex);
        } else {
            $regex = static::paramRegexType($type);
        }

        return compact('name', 'default', 'type', 'regex');
    }

    public static function paramFixType($value, $type)
    {
        if ($type === 'int') {
            return (int) $value;
        }

        if ($type === 'uint') {
            return (int) ($value < 0 ? 0 : $value);
        }

        if ($type === 'bool' or $type === 'boolean') {
            return (bool) $value;
        }

        throw new RoutingException('Unknown parameter type `' . $type . '`.');
    }

    public static function paramRegexType($type): ?string
    {
        if ($type === 'int') {
            return '-?[0-9]+';
        }

        if ($type === 'uint') {
            return '[0-9]+';
        }

        if ($type === 'bool' or $type === 'boolean') {
            return '0|1';
        }

        return null;
    }

    public static function fetchMatchedParams(array $regexParams, array $matches): array
    {
        $params = [];

        foreach ($regexParams as $param) {
            if (Str::isEmpty($matches[$param['name']] ?? null)) {
                $params[$param['name']] = $param['default'];
            } else {
                $params[$param['name']] = $matches[$param['name']];
            }

            if ($params[$param['name']] !== null and $param['type']) {
                $params[$param['name']] = static::paramFixType($params[$param['name']], $param['type']);
            }
        }

        return $params;
    }

    private static function schemaMatchedInfo(array $matches, array $parts): array
    {
        $regex = null;

        $params = [];

        foreach ($parts as $key => $part) {
            if ($part) {
                $regex .= Regex::quote($part);
            }

            if (array_key_exists($key, $matches[0])) {
                if ($param = $matches['param'][$key]) {
                    $param = static::splitParam($param);

                    $params[] = [
                        'name'    => $param['name'],
                        'default' => $param['default'],
                        'type'    => $param['type'],
                    ];

                    $paramRegex = $param['regex'] ?: '[^\\/]+';

                    $regex .= "(?'{$param['name']}'{$paramRegex})" . $matches['paramOptional'][$key];
                } elseif ($segment = $matches['segment'][$key]) {
                    [$segmentRegex, $segmentParams] = static::schemaInfo($segment);

                    $params = array_merge($params, $segmentParams);

                    $regex .= "(?:{$segmentRegex})?";
                }
            }
        }

        return [$regex, $params];
    }

    private static function paramFetchRegex(string $regex): string
    {
        if ($regex === '*') {
            return '.+?';
        }

        $regex = Regex::disableGroups($regex);

        if ($regex[-1] == '+' or $regex[-1] == '*') {
            $regex .= '?';
        }

        return $regex;
    }
}
