# Greg PHP Routing

[![StyleCI](https://styleci.io/repos/70080128/shield?style=flat)](https://styleci.io/repos/70080128)
[![Build Status](https://travis-ci.org/greg-md/php-router.svg)](https://travis-ci.org/greg-md/php-router)
[![Total Downloads](https://poser.pugx.org/greg-md/php-router/d/total.svg)](https://packagist.org/packages/greg-md/php-router)
[![Latest Stable Version](https://poser.pugx.org/greg-md/php-router/v/stable.svg)](https://packagist.org/packages/greg-md/php-router)
[![Latest Unstable Version](https://poser.pugx.org/greg-md/php-router/v/unstable.svg)](https://packagist.org/packages/greg-md/php-router)
[![License](https://poser.pugx.org/greg-md/php-router/license.svg)](https://packagist.org/packages/greg-md/php-router)

A smarter router for web artisans.

# Table of contents:

* [Requirements](#requirements)
* [How It Works](#how-it-works)
* [Routing Schema](#routing-schema)
* [Router](#router)
* [Group Route](#group-route)
* [Request Route](#request-route)
* [Hidden Route](#hidden-route)

# Requirements

* PHP Version `^7.1`

# How It Works

**First of all**, you have to initialize a Router:

```php
$router = new \Greg\Routing\Router();
```

**Then**, set up some routes:

```php
$router->any('/', function() {
    return 'Hello World!';
}, 'home');

$router->get('/page/{page}.html', function($page) {
    return "Hello on page {$page}!";
}, 'page');

$router->post('/user/{id#uint}', 'UsersController@save', 'user.save');
```

> If you set an action like `Controller@action`, when dispatching it will instantiate the `UsersController` and call the `save` public method.

**Now**, you can dispatch URLs path:

```php
echo $router->dispatch('/'); // result: Hello World!

// Initialize "UsersController" and execute "save" method.
echo $router->dispatch('/user/1', 'POST');
```

**and**, get URLs for them:

```php
$router->url('home'); // result: /

$router->url('home', ['foo' => 'bar']); // result: /?foo=bar

$router->url('user.save', ['id' => 1]); // result: /user/id

$router->url('user.save', ['id' => 1, 'debug' => true]); // result: /user/id?debug=true
```

**Optionally**, you can add a dispatcher to manage actions.

Let say you want to add the `Action` suffix to action names:

```php
$router->setDispatcher(function ($action) {
    if (is_callable($action)) {
        return $action;
    }

    return $action . 'Action';
});
```

**Also**, you can inverse the control of the controllers.

Let say you want instantiate the controller with some custom data,
or use some external IoC interface and run the init method if exists.

```php
$router->setIoc(function ($controllerName) {
    // Let say you already have an IoC container.
    global $iocContainer;

    $controller = $iocContainer->load($controllerName);

    if (method_exists($controller, 'init')) {
        $controller->init();
    }

    return $controller;
});
```

# Routing Schema

Routing schema supports **parameters** and **optional segments**.

**Parameter** format is `{<name>[:<default>][#<type>][|<regex>]}?`.

`<name>` - Parameter name;  
`<default>` - Default value;  
`<type>` - Parameter type. Supports `int`, `uint`, `boolean`(or `bool`);  
`<regex>` - Parameter regex;  
`?` - Question mark from the end determine if the parameter should be optional.

> Only `<name>` is required in the parameter.

**Optional segment** format is `[<schema>]`. Is working recursively.

`<schema>` - Any [routing schema](#routing-schema).

> It is very useful when you want to use the same action with different routing schema.

### Example

Let say we have a page with all articles of the same type, including pagination. The route for this page will be:

```php
$router->get('/articles/{type:lifestyle|[a-z0-9-]+}[/page-{page:1#uint}]', 'ArticlesController@type', 'articles.type');
```

`type` parameter is required in the route. Default value is `lifestyle` and should consist of **letters, numbers and dashes**.

`page` parameter is required in its segment, but the segment entirely is optional. Default value is `1` and should consist of **unsigned integers**.
If the parameter will not be set or will be the same as default value, the entire segment will be excluded from the URL path.

```php
echo $router->url('articles.type'); // result: /articles/lifestyle

echo $router->url('articles.type', ['type' => 'travel']); // result: /articles/travel

echo $router->url('articles.type', ['type' => 'travel', 'page' => 1]); // result: /articles/travel

echo $router->url('articles.type', ['type' => 'travel', 'page' => 2]); // result: /articles/travel/page-2
```

As you can see, there are no more URLs where you can get duplicated content, which is best for SEO.
In this way, you can easily create good user friendly URLs.

# Router

Below you can find a list of **supported methods** of the `Router`.

* [url](#url) - Fetch an URL of a route;
* [dispatch](#dispatch) - Dispatch an URL path;
* [any](#any) - Create a route for any request method;
* [request](#request) - Create a route for a specific request method;
    * [get](#request) - Create a GET route;
    * [head](#request) - Create a HEAD route;
    * [post](#request) - Create a POST route;
    * [put](#request) - Create a PUT route;
    * [delete](#request) - Create a DELETE route;
    * [connect](#request) - Create a CONNECT route;
    * [options](#request) - Create a OPTIONS route;
    * [trace](#request) - Create a TRACE route;
    * [patch](#request) - Create a PATCH route;
* [hidden](#hidden) - Create a hidden route. You can not dispatch it, but you can generate URLs from it;
* [group](#group) - Create a group of routes;
* [find](#find) - Find a route by name;
* [bind](#bind) - Set an input/output binder for a parameter;
* [bindStrategy](#bindstrategy) - Set an input/output binder for a parameter, using strategy;
* [bindIn](#bindin) - Set an input binder for a parameter;
* [bindInStrategy](#bindinstrategy) - Set an input binder for a parameter, using strategy;
* [binderIn](#binderin) - Get the input binder of a parameter;
* [bindInParam](#bindinparam) - Bind an input parameter;
* [bindOut](#bindout) - Set an output binder for a parameter;
* [bindOutStrategy](#bindoutstrategy) - Set an output binder for a parameter, using strategy;
* [binderOut](#binderout) - Get the output binder of a parameter;
* [bindOutParam](#bindoutparam) - Bind an output parameter;
* [setDispatcher](#setdispatcher) - Set an action dispatcher;
* [getDispatcher](#getdispatcher) - Get the actions dispatcher;
* [setIoc](#setioc) - Set an inversion of control for controllers;
* [getIoc](#getioc) - Get the inversion of control;
* [setNamespace](#setnamespace) - Set a namespace;
* [getNamespace](#getnamespace) - Get the namespace;
* [setErrorAction](#seterroraction) - Set an error action;
* [getErrorAction](#geterroraction) - Get the error action;
* [setHost](#sethost) - Set a host;
* [getHost](#gethost) - Get the host.

## url

Get the URL of a route.

```php
url(string $name, array $params = []): string
```

_Example:_

```php
$router->get('/page/{page}.html', function($page) {
    return "Hello on page {$page}!";
}, 'page');

$router->url('page', ['page' => 'terms']); // result: /page/terms.html

$router->url('page', ['page' => 'terms', 'foo' => 'bar']); // result: /page/terms.html?foo=bar
```

## dispatch

Dispatch an URL path.

```php
dispatch(string $name, array $params = []): string
```

_Example:_

```php
echo $router->dispatch('/'); // Dispatch any route

echo $router->dispatch('/user/1', 'POST'); // Dispatch a POST route
```

# Group Route

**Magic methods:**
* [__construct](#__construct)

Below you can find a list of **supported methods**.

* [match](#match) - Match a path against routes;
* [schema](#schema) - Get the schema;
* [schemaInfo](#schemaInfo) - Get information about schema;
* [setParent](#setParent) - Set parent routing;
* [getParent](#getParent) - Get parent routing;
* [path](#path) - Generate the path;
* [any](#any) - Create a route for any request method;
* [request](#request) - Create a route for a specific request method;
    * [get](#request) - Create a GET route;
    * [head](#request) - Create a HEAD route;
    * [post](#request) - Create a POST route;
    * [put](#request) - Create a PUT route;
    * [delete](#request) - Create a DELETE route;
    * [connect](#request) - Create a CONNECT route;
    * [options](#request) - Create a OPTIONS route;
    * [trace](#request) - Create a TRACE route;
    * [patch](#request) - Create a PATCH route;
* [hidden](#hidden) - Create a hidden route. You can not dispatch it, but you can generate URLs from it;
* [group](#group) - Create a group of routes;
* [find](#find) - Find a route by name;
* [bind](#bind) - Set an input/output binder for a parameter;
* [bindStrategy](#bindstrategy) - Set an input/output binder for a parameter, using strategy;
* [bindIn](#bindin) - Set an input binder for a parameter;
* [bindInStrategy](#bindinstrategy) - Set an input binder for a parameter, using strategy;
* [binderIn](#binderin) - Get the input binder of a parameter;
* [bindInParam](#bindinparam) - Bind an input parameter;
* [bindOut](#bindout) - Set an output binder for a parameter;
* [bindOutStrategy](#bindoutstrategy) - Set an output binder for a parameter, using strategy;
* [binderOut](#binderout) - Get the output binder of a parameter;
* [bindOutParam](#bindoutparam) - Bind an output parameter;
* [setDispatcher](#setdispatcher) - Set an action dispatcher;
* [getDispatcher](#getdispatcher) - Get the actions dispatcher;
* [setIoc](#setioc) - Set an inversion of control for controllers;
* [getIoc](#getioc) - Get the inversion of control;
* [setNamespace](#setnamespace) - Set a namespace;
* [getNamespace](#getnamespace) - Get the namespace;
* [setErrorAction](#seterroraction) - Set an error action;
* [getErrorAction](#geterroraction) - Get the error action;
* [setHost](#sethost) - Set a host;
* [getHost](#gethost) - Get the host.

## __construct

Initialize the route group.

```php
__construct(string $schema)
```

_Example:_

```php
$group = new \Greg\Routing\GroupRoute('/api/v1');

$group->get('/user');
```

## match

Match a path against routes.

```php
match(string $path, ?string $method = null, \Greg\Routing\RouteStrategy &$route = null, \Greg\Routing\RouteData &$data = null): bool
```

_Example:_

```php
if ($group->match('/', 'GET', $route, $data)) {
    echo $route->exec($data);
}
```

## schema

Get the schema.

```php
schema(): ?string
```

## schemaInfo

Get information about schema.

```php
schemaInfo(): ['regex', 'params']
```

## setParent

Set parent routing.

```php
setParent(RoutesAbstract $parent): $this
```

## getParent

Get parent routing.

```php
getParent(): RoutesAbstract
```

## path

Generate the path.

```php
path(array $params = []): array
```

# Request Route

**Magic methods:**
* [__construct](#__construct)

Below you can find a list of **supported methods**.

* [match](#match) - Match a path against routes;
* [exec](#exec) - Execute the route;
* [url](#url) - Fetch an URL for the route;
* [schema](#schema) - Get the schema;
* [schemaInfo](#schemaInfo) - Get information about schema;
* [setParent](#setParent) - Set parent routing;
* [getParent](#getParent) - Get parent routing;
* [path](#path) - Generate the path;
* [bind](#bind) - Set an input/output binder for a parameter;
* [bindStrategy](#bindstrategy) - Set an input/output binder for a parameter, using strategy;
* [bindIn](#bindin) - Set an input binder for a parameter;
* [bindInStrategy](#bindinstrategy) - Set an input binder for a parameter, using strategy;
* [binderIn](#binderin) - Get the input binder of a parameter;
* [bindInParam](#bindinparam) - Bind an input parameter;
* [bindOut](#bindout) - Set an output binder for a parameter;
* [bindOutStrategy](#bindoutstrategy) - Set an output binder for a parameter, using strategy;
* [binderOut](#binderout) - Get the output binder of a parameter;
* [bindOutParam](#bindoutparam) - Bind an output parameter;
* [setDispatcher](#setdispatcher) - Set an action dispatcher;
* [getDispatcher](#getdispatcher) - Get the actions dispatcher;
* [setIoc](#setioc) - Set an inversion of control for controllers;
* [getIoc](#getioc) - Get the inversion of control;
* [setErrorAction](#seterroraction) - Set an error action;
* [getErrorAction](#geterroraction) - Get the error action;
* [setHost](#sethost) - Set a host;
* [getHost](#gethost) - Get the host.

## __construct

Initialize the request route.

```php
__construct(string $schema, $action)
```

_Example:_

```php
$route = new \Greg\Routing\RequestRoute('/users', 'UsersController@index');

$route->exec();
```

## match

Match a path against route.

```php
match(string $path, RouteData &$data = null): bool
```

_Example:_

```php
if ($route->match('/', $data)) {
    print_r($data->params());
}
```

## exec

Execute the route.

```php
exec(RouteData $data): string
```

_Example:_

```php
$route->exec(new RouteData('/', ['foo' => 'bar']));
```

## url

Fetch an URL for the route.

```php
url(array $params = []): string
```

_Example:_

```php
$url = $route->url(['foo' => 'bar']);
```

# Hidden Route

**Magic methods:**
* [__construct](#__construct)

Below you can find a list of **supported methods**.

* [url](#url) - Fetch an URL for the route;
* [schema](#schema) - Get the schema;
* [schemaInfo](#schemaInfo) - Get information about schema;
* [setParent](#setParent) - Set parent routing;
* [getParent](#getParent) - Get parent routing;
* [path](#path) - Generate the path;
* [bindOut](#bindout) - Set an output binder for a parameter;
* [bindOutStrategy](#bindoutstrategy) - Set an output binder for a parameter, using strategy;
* [binderOut](#binderout) - Get the output binder of a parameter;
* [bindOutParam](#bindoutparam) - Bind an output parameter;
* [setHost](#sethost) - Set a host;
* [getHost](#gethost) - Get the host.

## __construct

Initialize the request route.

```php
__construct(string $schema)
```

_Example:_

```php
$route = new \Greg\Routing\HiddenRoute('/users');

$route->exec();
```

# Routing Abstract

## any

Create a route for any request method.

```php
any(string $schema, $action, ?string $name = null): \Greg\Routing\RequestRoute
```

## request

Create a route for a specific request method.

```php
request(string $schema, $action, ?string $name = null, ?string $method = null): \Greg\Routing\RequestRoute
```

You can also create a specific request method by calling the method name directly.
Available types are: `GET`, `HEAD`, `POST`, `PUT`, `DELETE`, `CONNECT`, `OPTIONS`, `TRACE`, `PATCH`.

```php
[get|head|post|put|delete|connect|options|trace|patch](string $schema, $action, ?string $name = null): \Greg\Routing\RequestRoute
```

_Example:_

```php
$router->get('/users', 'UsersController@index', 'users');

$router->post('/users/add', 'UsersController@add', 'users.add');
```

## hidden

Create a hidden route. You can not dispatch it, but you can generate URLs from it.

```php
hidden(string $schema, string $name): \Greg\Routing\HiddenRoute
```

_Example:_

```php
$router->hidden('/catalog/{name}', 'partner.catalog')->setHost('mypartner.com');

$router->url('partner.catalog', ['name' => 'cars']); // result: http://mypartner.com/catalog/cars
```

## group

Create a group of routes.

```php
group(string $schema, ?string $prefix, callable $callable): \Greg\Routing\GroupRoute
```

_Example:_

```php
$router->group('/api', 'api.', function (\Greg\Routing\GroupRoute $group) {
    $group->group('/v1', 'v1.', function (\Greg\Routing\GroupRoute $group) {
        $group->any('/users', 'UsersController@index', 'users');
    });

    $group->group('/v2', 'v2.', function (\Greg\Routing\GroupRoute $group) {
        $group->any('/users', 'UsersController@index', 'users');
        
        $group->any('/clients', 'ClientsController@index', 'clients');
    });
});

$router->url('api.v1.users'); // result: /api/v1/users

$router->url('api.v1.clients'); // throws: \Greg\Routing\RoutingException

$router->url('api.v2.clients'); // result: /api/v2/clients
```

## find

Find a route by name.

```php
find(string $name): ?\Greg\Routing\FetchRouteStrategy
```

_Example:_

```php
$route = $router->find('users.save');

$route->url(['foo' => 'bar']);
```

## bind

Set an input/output binder for a parameter.

```php
bind($name, callable $callableIn, ?callable $callableOut = null): $this
```

_Example:_

```php
$this->bind('id', function($id) {
    $user = (object) ['id' => $id];

    return $user;
}, function($user) {
    return $user->id;
});
```

## bindStrategy

Set an input/output binder for a parameter, using strategy.

```php
bindStrategy(string $name, \Greg\Routing\BindInOutStrategy $strategy): $this
```

_Example:_

```php
$this->bindStrategy('id', new class implements BindInOutStrategy {
    public function input($id)
    {
        $user = (object) ['id' => $id];

        return $user;
    }

    public function output($user)
    {
        return $user->id;
    }
});
```

## bindIn

Set an input binder for a parameter.

```php
bindIn($name, callable $callable): $this
```

_Example:_

```php
$this->bindIn('id', function($id) {
    $user = (object) ['id' => $id];

    return $user;
});
```

## bindInStrategy

Set an input binder for a parameter, using strategy.

```php
bindInStrategy($name, \Greg\Routing\BindInStrategy $callable): $this
```

_Example:_

```php
$this->bindInStrategy('id', new class implements \Greg\Routing\BindInStrategy {
    public function input($id)
    {
        $user = (object) ['id' => $id];

        return $user;
    }
});
```

## binderIn

Get the input binder of a parameter.

```php
binderIn(string $name): \Greg\Routing\BindInStrategy|callable
```

_Example:_

```php
$binder = $router->binderIn('id');

if (is_callable($binder)) {
    $user = $binder(1);
} else {
    $user = $binder->input(1);
}
```

## bindInParam

Bind an input parameter.

```php
bindInParam(string $name, $value): mixed
```

_Example:_

```php
$user = $router->bindInParam('id', 1);
```

## bindOut

Set an output binder for a parameter.

```php
bindOut($name, callable $callable): $this
```

_Example:_

```php
$this->bindOut('id', function($user) {
    return $user->id;
});
```

## bindOutStrategy

Set an output binder for a parameter, using strategy.

```php
bindOutStrategy($name, \Greg\Routing\BindOutStrategy $callable): $this
```

_Example:_

```php
$this->bindOutStrategy('id', new class implements \Greg\Routing\BindOutStrategy {
    public function output($user)
    {
        return $user->id;
    }
});
```

## binderOut

Get the output binder of a parameter.

```php
binderOut(string $name): \Greg\Routing\BindOutStrategy|callable
```

_Example:_

```php
$binder = $router->binderOut('id');

if (is_callable($binder)) {
    $id = $binder($user);
} else {
    $id = $binder->output($user);
}
```

## bindOutParam

Bind an output parameter.

```php
bindOutParam(string $name, $value): mixed
```

_Example:_

```php
$user = $router->bindOutParam('id', 1);
```

## setDispatcher

Set an action dispatcher.

```php
setDispatcher(callable $callable): $this
```

_Example:_

Let say you want to add the `Action` suffix to action names:

```php
$router->setDispatcher(function ($action) {
    if (is_callable($action)) {
        return $action;
    }

    return $action . 'Action';
});
```

## getDispatcher

Get the actions dispatcher.

```php
getDispatcher(): callable
```

## setIoc

Set an inversion of control for controllers.

```php
setIoc(callable $callable): $this
```

_Example:_

Let say you want instantiate the controller with some custom data,
or use some external IoC interface and run the init method if exists.

```php
$router->setIoc(function ($controllerName) {
    // Let say you already have an IoC container.
    global $iocContainer;

    $controller = $iocContainer->load($controllerName);

    if (method_exists($controller, 'init')) {
        $controller->init();
    }

    return $controller;
});
```

## getIoc

Get the inversion of control.

```php
getIoc(): callable
```

## setNamespace

Set a namespace.

```php
setNamespace(string $namespace): $this
```

_Example:_

```php
$router->setNamespace('Http');
```

## getNamespace

Get the namespace.

```php
getNamespace(): string
```

## setErrorAction

Set error action.

```php
setErrorAction($action): $this
```

_Example:_

```php
$router->setErrorAction(function() {
    return 'Ooops! Something has gone wrong.'
});
```

## getErrorAction

Get error action.

```php
getErrorAction(): mixed
```

## setHost

Set a host.

```php
setHost(string $host): $this
```

_Example:_

```php
$router->setHost('example.com');
```

## getHost

Get the host.

```php
getHost(): string
```

# License

MIT © [Grigorii Duca](http://greg.md)
