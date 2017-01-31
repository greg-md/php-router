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

**Optionally**, you can add an action dispatcher to make some changes for the action.

Let say you want to add the `Action` suffix for action names:
```php
$router->setDispatcher(function ($action) {
    if (is_callable($action)) {
        return $action;
    }

    return $action . 'Action';
});
```

**Also**, you can inverse the control of the controller.

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
* [url](#url) - Get the URL of a route;
* [bind](#bind) - Set an input/output binder for a parameter;
* [bindCallable](#bindCallable) - Set an input/output binder for a parameter, using callable's;
* [bindIn](#bindIn) - Set an input binder for a parameter;
* [binderIn](#binderIn) - Get the input binder of a parameter;
* [bindInParam](#bindInParam) - Bind an input parameter;
* [bindOut](#bindOut) - Set an output binder for a parameter;
* [binderOut](#binderOut) - Get the output binder of a parameter;
* [bindOutParam](#bindOutParam) - Bind an output parameter;
* [setErrorAction](setErrorAction) - Set error action;
* [getErrorAction](getErrorAction) - Get error action;
* [setDispatcher](setDispatcher) - Set dispatcher;
* [getDispatcher](getDispatcher) - Get dispatcher;
* [setHost](setHost) - Set host;
* [getHost](getHost) - Get host;
* [dispatch](#dispatch) - Dispatch an URL path;
* [findRoute](#findRoute) - Find a route by name;

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

You can also create a route method by calling the method name directly.
Available types are: `GET`, `HEAD`, `POST`, `PUT`, `DELETE`, `CONNECT`, `OPTIONS`, `TRACE`, `PATCH`.

```php
[get|head|post|put|delete|connect|options|trace|patch](string $schema, $action, ?string $name = null): \Greg\Routing\RequestRoute
```

_Example:_

```php
$router->get('/users', function() {}, 'users');

$router->post('/users/add', function() {}, 'users.add');
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
$router->group('/api', 'api.', function (GroupRoute $group) {
    $group->group('/v1', 'v1.', function (GroupRoute $group) {
        $group->any('/users', function () { return 'Users data'; }, 'users');
    });

    $group->group('/v2', 'v2.', function (GroupRoute $group) {
        $group->any('/users', function () { return 'Users data'; }, 'users');
        
        $group->any('/clients', function () { return 'Clients data'; }, 'clients');
    });
});

$router->url('api.v1.users'); // result: /api/v1/users

$router->url('api.v1.clients'); // throws: \Greg\Routing\RoutingException

$router->url('api.v2.clients'); // result: /api/v2/clients
```
