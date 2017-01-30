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

**Optionally**, you can add an action dispatcher to support custom action types.
The dispatcher should return a callable of the action.

Let say you want to support an action format like `Controller@action`:

```php
$router->setDispatcher(function ($action) {
    [$controllerName, $actionName] = explode('@', $action);
    
    return [new $controllerName, $actionName];
});
```

**Then**, set up some routes:

```php
$router->any('/', function() {
    return 'Hello World!';
});

$router->post('/user/{id#uint}', 'UsersController@save');
```

**Now**, you can dispatch actions:

```php
echo $router->dispatch('/'); // result: Hello World!

// Initialize "UsersController" and execute "save" method.
echo $router->dispatch('/user/1', 'POST');
```