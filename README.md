# Greg PHP Routing

[![StyleCI](https://styleci.io/repos/70080128/shield?style=flat)](https://styleci.io/repos/70080128)
[![Build Status](https://travis-ci.org/greg-md/php-router.svg)](https://travis-ci.org/greg-md/php-router)
[![Total Downloads](https://poser.pugx.org/greg-md/php-router/d/total.svg)](https://packagist.org/packages/greg-md/php-router)
[![Latest Stable Version](https://poser.pugx.org/greg-md/php-router/v/stable.svg)](https://packagist.org/packages/greg-md/php-router)
[![Latest Unstable Version](https://poser.pugx.org/greg-md/php-router/v/unstable.svg)](https://packagist.org/packages/greg-md/php-router)
[![License](https://poser.pugx.org/greg-md/php-router/license.svg)](https://packagist.org/packages/greg-md/php-router)

A smarter router for web artisans.

# Requirements

* PHP Version `^7.1`

# How It Works

**First of all**, you have to initialize a Router:

```php
$router = new \Greg\Routing\Router();
```

**Optionally**, you can add an action dispatcher to support custom actions.
The dispatcher should return a callable of the action.

Let say you want to support an action like `Controller@action`:

```php
$router->setDispatcher(function ($action): callable {
    [$controllerName, $actionName] = explode('@', $action);
    
    return [new $controllerName, $actionName];
});
```

**Then**, set up some routes:

```php
$router->any('/', function() {
    return 'Hello World!';
}, 'home');

$router->post('/user/{id#uint}', 'UsersController@save', 'user.save');
```

**Now**, you can dispatch actions:

```php
echo $router->dispatch('/'); // result: Hello World!

// Initialize "UsersController" and execute "save" method.
echo $router->dispatch('/user/1', 'POST');
```

**And**, get URLs for them:

```php
$router->url('home'); // result: /

$router->url('home', ['foo' => 'bar']); // result: /?foo=bar

$router->url('user.save', ['id' => 1]); // result: /user/id

$router->url('user.save', ['id' => 1, 'debug' => true]); // result: /user/id?debug=true
```

# Routing Schema

Routing schema supports **parameters** and **optional segments**.

**Parameter** format is `{<name>[:<default>[#<type>[|<regex>]]]}?`.

`<name>` - Parameter name;  
`<default>` - Default value;  
`<type>` - Parameter type. Supports `int`, `uint`, `boolean`(alias `bool`);  
`<regex>` - Parameter regex;  
`?` - Question symbol from the end determine if the parameter is optional or not.

> Only `<name>` is required in the parameter.

**Optional segment** format is `[<schema>]`. Is working recursively.

`<schema>` - Any [routing schema](#routing-schema).

> It is very useful when you want to use the same action with different routing schema.

_Example:_

Let say we have a page with all articles of the same type, including pagination. The route for this page will be:

```
$router->get('/articles/{type:lifestyle|[a-z0-9-]+}[/page-{page:1#uint}]', 'ArticlesController@type', 'articles.type');
```

Parameter `type` is required in the route. Its default value is `lifestyle` and should consist of letters, numbers and dashes.

Parameter `page` is required in its segment, but the segment entirely is optional.
If the `page` will not be set, the entire segment will be excluded from the URL.

```php
echo $router->url('articles.type'); // result: /articles/lifestyle

echo $router->url('articles.type', ['type' => 'travel']); // result: /articles/travel

echo $router->url('articles.type', ['type' => 'travel', 'page' => 1]); // result: /articles/travel

echo $router->url('articles.type', ['type' => 'travel', 'page' => 2]); // result: /articles/travel/page-2
```

As you can see, there are no more URLs where you can get duplicated content, which best for SEO.
In this way, you can easily create good user friendly URLs.

# Router

