<?php

namespace Greg\Router;

use Greg\Support\Accessor\ArrayAccessTrait;
use Greg\Support\Arr;
use Greg\Support\Http\Request;
use Greg\Support\IoC\IoCManagerInterface;
use Greg\Support\Obj;
use Greg\Support\Regex;
use Greg\Support\Regex\InNamespaceRegex;
use Greg\Support\Str;
use Greg\Support\Url;

class Route implements \ArrayAccess
{
    use ArrayAccessTrait, RouterTrait;

    const TYPE_GET = 'get';

    const TYPE_POST = 'post';

    const TYPE_HIDDEN = 'hidden';

    const TYPE_GROUP = 'group';

    protected $format = null;

    protected $action = null;

    protected $name = null;

    protected $type = null;

    protected $host = null;

    protected $data = [];

    protected $strict = true;

    protected $encodeValues = true;

    protected $delimiter = '/';

    protected $regexMatchDelimiter = '/';

    protected $middleware = [];

    protected $defaultParams = [];

    protected $onMatch = [];

    protected $path = null;

    protected $cleanParams = [];

    /**
     * @var Route|null
     */
    protected $parent = null;

    /**
     * @var Router|null
     */
    protected $router = null;

    public function __construct($format, callable $action = null, IoCManagerInterface $ioCManager = null)
    {
        $this->setFormat($format);

        if ($action) {
            $this->setAction($action);
        }

        if ($ioCManager) {
            $this->setIoCManager($ioCManager);
        }

        return $this;
    }

    public function createRoute($format, callable $action = null)
    {
        return (new Route($format, $action, $this->getIoCManager()))->setParent($this);
    }

    protected function regexPattern()
    {
        $curlyBrR = new InNamespaceRegex('{', '}', false);

        $squareBrR = new InNamespaceRegex('[', ']');

        $findRegex = "(?:{$curlyBrR}(\\?)?)|(?:{$squareBrR}(\\?)?)";

        $pattern = Regex::pattern($findRegex);

        return $pattern;
    }

    protected function compile($format)
    {
        $compiled = null;

        $params = [];

        $defaults = [];

        $pattern = $this->regexPattern();

        // find all "{param}?" and "[format]?"
        if (preg_match_all($pattern, $format, $matches)) {
            $paramKey = 1;
            $paramRK = 2;

            $subFormatKey = 3;
            $subFormatRK = 4;

            // split remain string
            $parts = preg_split($pattern, $format);

            foreach ($parts as $key => $remain) {
                if ($remain) {
                    $compiled .= Regex::quote($remain);
                }

                if (array_key_exists($key, $matches[0])) {
                    if ($param = $matches[$paramKey][$key]) {
                        list($paramName, $paramDefault, $paramRegex) = $this->splitParam($param);

                        $params[] = $paramName;

                        if ($paramDefault) {
                            $defaults[$paramName] = $paramDefault;
                        }

                        $compiled .= "({$paramRegex})" . $matches[$paramRK][$key];
                    } elseif ($subFormat = $matches[$subFormatKey][$key]) {
                        list($subCompiled, $subParams, $subDefaults) = $this->compile($subFormat);

                        $compiled .= "(?:{$subCompiled})" . $matches[$subFormatRK][$key];

                        $params = array_merge($params, $subParams);

                        $defaults = array_merge($defaults, $subDefaults);
                    }
                }
            }
        } else {
            $compiled = Regex::quote($format);
        }

        return [$compiled, $params, $defaults];
    }

    protected function splitParam($param)
    {
        $name = $param;

        $default = $regex = null;

        // extract from var:default|regex
        if (preg_match(Regex::pattern('^((?:\\\:|\\\||[^\:])+?)(?:\:((?:|\\\||[^\|])+?))?(?:\|(.+?))?$'), $param, $matches)) {
            $name = $matches[1];

            $greedy = false;

            $nameLen = mb_strlen($name);

            if ($name[$nameLen - 1] == '?') {
                $name = mb_substr($name, 0, $nameLen - 1);

                $greedy = true;
            }

            $default = Arr::get($matches, 2);

            $regex = Arr::has($matches, 3) ? Regex::disableGroups($matches[3]) : null;

            if (!$regex) {
                $regex = ($this->regexMatchDelimiter() ? '.+' : '[^' . Regex::quote($this->getDelimiter()) . ']+');

                if ($greedy) {
                    $regex .= '?';
                }
            }
        }

        return [$name, $default, $regex];
    }

    public function dispatch(array $params = [])
    {
        try {
            $this->runBeforeMiddleware();

            if ($action = $this->getAction()) {
                $result = $this->dispatchAction($action, $params);
            } else {
                $result = null;
            }

            $this->runAfterMiddleware();

            return $result;
        } catch (\Exception $e) {
            return $this->dispatchException($e);
        }
    }

    public function addMiddleware($object)
    {
        if (!is_object($object)) {
            throw new \Exception('Middleware is not an object.');
        }

        $this->middleware[] = $object;

        return $this;
    }

    public function getMiddleware()
    {
        return $this->middleware;
    }

    public function getAllMiddleware()
    {
        $middleware = $this->middleware;

        if ($parent = $this->getParent()) {
            $parentMiddleware = $parent->getAllMiddleware();

            $middleware = array_merge($parentMiddleware, $middleware);
        }

        return $middleware;
    }

    protected function runBeforeMiddleware()
    {
        foreach ($this->getAllMiddleware() as $middleware) {
            if ($this->execBeforeMiddleware($middleware) === false) {
                break;
            }
        }

        return $this;
    }

    protected function runAfterMiddleware()
    {
        foreach ($this->getAllMiddleware() as $middleware) {
            if ($this->execAfterMiddleware($middleware) === false) {
                break;
            }
        }

        return $this;
    }

    protected function execBeforeMiddleware($middleware)
    {
        if (method_exists($middleware, 'routerBeforeMiddleware')) {
            return Obj::callCallableWith([$middleware, 'routerBeforeMiddleware'], $this);
        }

        return true;
    }

    protected function execAfterMiddleware($middleware)
    {
        if (method_exists($middleware, 'routerAfterMiddleware')) {
            return Obj::callCallableWith([$middleware, 'routerAfterMiddleware'], $this);
        }

        return true;
    }

    protected function dispatchAction(callable $action, array $params = [])
    {
        return Obj::callCallable($action, $params + $this->params() + $this->getDefaultParams(), $this);
    }

    protected function dispatchException(\Exception $e)
    {
        if ($errorAction = $this->getErrorAction()) {
            return (new Route('', $errorAction))->dispatch([
                'exception' => $e,
            ]);
        }

        throw $e;
    }

    public function match($path, array &$matchedParams = [])
    {
        if ($this->isHidden()) {
            return false;
        }

        list($compiled, $compiledParams, $compiledDefaults) = $this->compile($this->getFormat());

        $pattern = '^' . $compiled . (($this->isGroup() or !$this->strict()) ? '(.*)' : '') . '$';

        if (preg_match(Regex::pattern($pattern), $path, $matches)) {
            array_shift($matches);

            $matchedRoute = false;

            if ($this->isGroup()) {
                $subPath = array_pop($matches);

                foreach ($this->routes as $route) {
                    if ($subMatchedRoute = $route->match($subPath)) {
                        $matchedRoute = $subMatchedRoute;

                        break;
                    }
                }
            } else {
                $matchedRoute = $this;
            }

            if ($matchedRoute) {
                $params = [];

                foreach ($compiledParams as $key => $param) {
                    if (array_key_exists($key, $matches) and !Str::isEmpty($matches[$key])) {
                        $params[$param] = $this->decode($matches[$key]);
                    } else {
                        $params[$param] = Arr::get($compiledDefaults, $param);
                    }
                }

                if (!$this->isGroup() and !$this->strict()) {
                    $remain = array_pop($matches);

                    $remain = Str::splitPath($remain, $this->getDelimiter());

                    $params = array_merge($params, $this->chunkParams($remain));
                }

                $params += $compiledDefaults;

                $cleanParams = $params;

                $params = $this->bindInRouteParams($params);

                $this->setPath($path);

                $this->setCleanParams($cleanParams);

                $this->setParams($params);

                if ($matchedRoute !== $this) {
                    $matchedRoute->setPath($path);

                    $matchedRoute->setCleanParams($cleanParams);

                    $matchedRoute->setParams($params);
                }

                $matchedParams = $params;

                foreach ($this->onMatch as $callable) {
                    Obj::callCallableWith($callable, $matchedRoute);
                }
            }

            return $matchedRoute;
        }

        return false;
    }

    public function onMatch(callable $callable)
    {
        $this->onMatch[] = $callable;

        return $this;
    }

    public function bindInRouteParams(array $params)
    {
        $params = $this->bindInParams($params);

        if ($router = $this->getRouter()) {
            $params = $router->bindInParams($params);
        }

        return $params;
    }

    public function hasRouteBoundOut($name)
    {
        if ($this->hasBoundOut($name)) {
            return true;
        }

        if ($parent = $this->getParent()) {
            return $parent->hasRouteBoundOut($name);
        }

        if ($router = $this->getRouter()) {
            return $router->hasBoundOut($name);
        }

        return false;
    }

    public function getRouteBoundOutParam($name, array $params = [])
    {
        if ($this->hasBoundOut($name)) {
            return $this->getBoundOutParam($name, $params);
        }

        if ($parent = $this->getParent()) {
            return $parent->getRouteBoundOutParam($name, $params);
        }

        if ($router = $this->getRouter()) {
            return $router->getBoundOutParam($name, $params);
        }

        return null;
    }

    public function isGet()
    {
        return $this->getType() == static::TYPE_GET;
    }

    public function isPost()
    {
        return $this->getType() == static::TYPE_POST;
    }

    public function isHidden()
    {
        return $this->getType() == static::TYPE_HIDDEN;
    }

    public function isGroup()
    {
        return $this->getType() == static::TYPE_GROUP;
    }

    public function chunkParams($params)
    {
        $params = array_chunk($params, 2);

        $return = [];

        foreach ($params as $param) {
            $return[$this->decode($param[0])] = $this->decode(Arr::get($param, 1));
        }

        return $return;
    }

    public function fetchPath(array &$params = [])
    {
        $params = array_filter($params);

        list($compiled) = $this->fetchFormat($this->getFormat(), $params);

        if ($parent = $this->getParent()) {
            $params += $parent->params();

            $compiled = $parent->fetchPath($params) . ($compiled !== '/' ? $compiled : null);
        }

        return $compiled;
    }

    public function fetch(array $params = [], $full = false)
    {
        $compiled = $this->fetchPath($params);

        if (!$compiled) {
            $compiled = '/';
        }

        if ($params) {
            if ($this->strict()) {
                $compiled .= '?' . http_build_query($params);
            } else {
                $delimiter = $this->getDelimiter();

                // Need to make double encoding and then decode values
                $params = Arr::each($params, function ($value, $key) {
                    return [$this->encode($value), $this->encode($key)];
                });

                $compiled .= ($compiled !== $delimiter ? $delimiter : '') . implode($delimiter, Arr::pack($params, $delimiter));
            }
        }

        if ($host = $this->getHost()) {
            list($hostCompiled) = $this->fetchFormat($host);

            $compiled = $hostCompiled . $compiled;

            $compiled = Url::fix($compiled, Request::isSecured());
        } else {
            if ($full) {
                $compiled = Url::full($compiled);
            }
        }

        return $compiled;
    }

    protected function encode($value)
    {
        return $this->encodeValues() ? urlencode($value) : $value;
    }

    protected function decode($value)
    {
        return $this->encodeValues() ? urldecode($value) : $value;
    }

    protected function fetchFormat($format, &$params = [], $required = true)
    {
        $pattern = $this->regexPattern();

        $defaultParams = $usedParams = [];

        // find all "{param}?" and "[format]?"
        if (preg_match_all($pattern, $format, $matches)) {
            $paramKey = 1;
            $paramRK = 2;

            $subFormatKey = 3;
            $subFormatRK = 4;

            // split remain string
            $parts = preg_split($pattern, $format);

            // start from the last
            $parts = array_reverse($parts, true);

            $compiled = [];

            foreach ($parts as $key => $remain) {
                if (array_key_exists($key, $matches[0])) {
                    if ($param = $matches[$paramKey][$key]) {
                        list($paramName, $paramDefault) = $this->splitParam($param);

                        $paramRequired = !$matches[$paramRK][$key];

                        if (!Arr::has($params, $paramName) and $this->hasRouteBoundOut($paramName)) {
                            $value = $this->getRouteBoundOutParam($paramName, $params);
                        } else {
                            $value = Arr::get($params, $paramName, $paramDefault);
                        }

                        if ($paramRequired) {
                            if (Str::isEmpty($value)) {
                                if (!$required) {
                                    return null;
                                }
                                throw new \Exception('Param `' . $paramName . '` is required in route `' . ($this->getName() ?: $this->getFormat()) . '`.');
                            }

                            $compiled[] = $this->encode($value);
                        } else {
                            if ((!Str::isEmpty($value) and $value != $paramDefault) or $compiled) {
                                $compiled[] = $this->encode($value);
                            }
                        }

                        if ($paramDefault == $value) {
                            $defaultParams[] = $paramName;
                        } else {
                            $usedParams[] = $paramName;
                        }
                    } elseif ($subFormat = $matches[$subFormatKey][$key]) {
                        $subFormatRequired = !$matches[$subFormatRK][$key];

                        list($subCompiled, $subUsedParams) = $this->fetchFormat($subFormat, $params, $subFormatRequired);

                        if (!Str::isEmpty($subCompiled)) {
                            if ($subFormatRequired or $compiled or $subUsedParams) {
                                $compiled[] = $subCompiled;
                            }
                        }
                    }
                }

                if (!Str::isEmpty($remain)) {
                    $compiled[] = $remain;
                }
            }

            $compiled = array_reverse($compiled);

            $compiled = implode('', $compiled);
        } else {
            $compiled = $format;
        }

        $defaultParams and Arr::del($params, $defaultParams);

        if (!$required and !$usedParams) {
            return null;
        }

        $usedParams and Arr::del($params, $usedParams);

        return [$compiled, $usedParams];
    }

    public function setName($name)
    {
        $this->name = (string) $name;

        return $this;
    }

    public function getName()
    {
        return $this->name;
    }

    public function setType($name)
    {
        $this->type = (string) $name;

        return $this;
    }

    public function getType()
    {
        return $this->type;
    }

    public function setHost($name)
    {
        $this->host = (string) $name;

        return $this;
    }

    public function getHost()
    {
        return $this->name;
    }

    public function strict($type = null)
    {
        if (func_num_args()) {
            $this->strict = (bool) $type;

            return $this;
        }

        return $this->strict;
    }

    public function encodeValues($type = null)
    {
        if (func_num_args()) {
            $this->encodeValues = (bool) $type;

            return $this;
        }

        return $this->encodeValues;
    }

    public function setDelimiter($delimiter)
    {
        $this->delimiter = (string) $delimiter;

        return $this;
    }

    public function getDelimiter()
    {
        return $this->delimiter;
    }

    public function regexMatchDelimiter($type = null)
    {
        if (func_num_args()) {
            $this->regexMatchDelimiter = (bool) $type;

            return $this;
        }

        return $this->regexMatchDelimiter;
    }

    public function setData($key, $value = null)
    {
        if (is_array($key)) {
            $this->data = array_merge($this->data, $key);
        } else {
            $this->data[$key] = $value;
        }

        return $this;
    }

    public function hasData($key)
    {
        return array_key_exists($key, $this->data);
    }

    public function getData($key = null)
    {
        if (func_num_args()) {
            return $this->hasData($key) ? $this->data[$key] : null;
        }

        return $this->data;
    }

    public function setDefaultParams($key, $value = null)
    {
        if (is_array($key)) {
            $this->defaultParams = array_merge($this->defaultParams, $key);
        } else {
            $this->defaultParams[$key] = $value;
        }

        return $this;
    }

    public function hasDefaultParam($key)
    {
        return array_key_exists($key, $this->defaultParams);
    }

    public function getDefaultParams($key = null)
    {
        if (func_num_args()) {
            return $this->hasDefaultParam($key) ? $this->defaultParams[$key] : null;
        }

        return $this->defaultParams;
    }

    public function setFormat($format)
    {
        $this->format = (string) $format;

        return $this;
    }

    public function getFormat()
    {
        return $this->format;
    }

    public function setAction(callable $action)
    {
        $this->action = $action;

        return $this;
    }

    public function getAction()
    {
        return $this->action;
    }

    public function setPath($path = null)
    {
        $this->path = $path;

        return $this;
    }

    public function path()
    {
        return $this->path;
    }

    public function setParams(array $params)
    {
        return $this->setAccessor($params);
    }

    public function params($key = null)
    {
        return func_num_args() ? $this->getFromAccessor($key) : $this->getAccessor();
    }

    public function setCleanParams(array $params)
    {
        $this->cleanParams = $params;

        return $this;
    }

    public function getCleanParams($key = null)
    {
        if (func_num_args()) {
            return Arr::getRef($this->cleanParams, $key);
        }

        return $this->cleanParams;
    }

    public function setParent(Route $route)
    {
        $this->parent = $route;

        return $this;
    }

    public function getParent()
    {
        return $this->parent;
    }

    public function setRouter(Router $router)
    {
        $this->router = $router;

        return $this;
    }

    public function getRouter()
    {
        return $this->router;
    }
}
