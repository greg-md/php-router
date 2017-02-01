<?php

namespace Greg\Routing\Tests;

use Greg\Routing\Bind\BindInStrategy;
use Greg\Routing\Bind\BindOutStrategy;
use Greg\Routing\GroupRoute;
use Greg\Routing\RequestRoute;
use Greg\Routing\RouteData;
use Greg\Routing\Router;
use Greg\Routing\RoutingException;
use PHPUnit\Framework\TestCase;

class RouterTest extends TestCase
{
    /**
     * @var Router
     */
    private $router = null;

    protected function setUp()
    {
        parent::setUp();

        $this->router = new Router();
    }

    /** @test */
    public function it_dispatch_a_route()
    {
        $this->router->any('/', function () {
            return 'Hello World!';
        });

        $this->assertEquals('Hello World!', $this->router->dispatch('/'));

        $this->assertEquals('Hello World!', $this->router->dispatch('/', 'POST'));
    }

    /**
     * @test
     *
     * @dataProvider getRouteTypes
     *
     * @param $type
     */
    public function it_dispatch_a_route_type($type)
    {
        $_SERVER['REQUEST_METHOD'] = strtoupper($type);

        $this->router->{$type}('/', function () {
            return 'Hello World!';
        });

        $this->assertEquals('Hello World!', $this->router->dispatch('/', $_SERVER['REQUEST_METHOD']));
    }

    public function getRouteTypes()
    {
        foreach (['get', 'head', 'post', 'put', 'delete', 'connect', 'options', 'trace', 'patch'] as $key) {
            yield [$key];
        }
    }

    /** @test */
    public function it_gets_a_hidden_route()
    {
        $this->router->hidden('', 'home');

        $this->assertEquals('/', $this->router->url('home'));

        $this->router->hidden('/user/{id}', 'user');

        $this->assertEquals('/user/1?foo=bar', $this->router->url('user', ['id' => 1, 'foo' => 'bar']));
    }

    /** @test */
    public function it_dispatches_a_group()
    {
        $this->router->group('/api', 'api.', function (GroupRoute $group) {
            $group->group('/v2', 'v2.', function (GroupRoute $group) {
                $group->any('/users', function () {
                    return 'Users data';
                }, 'users');
            });

            $group->group('/v3', 'v3.', function (GroupRoute $group) {
                $group->any('/clients', function () {
                    return 'Clients data';
                }, 'clients');
            });
        });

        $this->assertEquals('/api/v3/clients', $this->router->url('api.v3.clients'));

        $this->assertEquals('Clients data', $this->router->dispatch('/api/v3/clients'));
    }

    /** @test */
    public function it_throws_if_dispatch_route_not_found()
    {
        $this->router->group('/api', 'api.', function (GroupRoute $group) {
            $group->group('/v2', 'v2.', function (GroupRoute $group) {
                $group->any('/users', function () {
                    return 'Users data';
                }, 'users');
            });
        });

        $this->expectException(RoutingException::class);

        $this->router->dispatch('/api/v2/clients');
    }

    /** @test */
    public function it_binds_in_params()
    {
        $this->router->group('', null, function (GroupRoute $group) {
            $group->bindInStrategy('id', new class() implements BindInStrategy {
                public function input($id)
                {
                    return (object) ['id' => $id];
                }
            });

            $this->assertObjectHasAttribute('id', $group->bindInParam('id', 1));
        });
    }

    /** @test */
    public function it_binds_out_params()
    {
        $group = new GroupRoute('');

        $group->bindOutStrategy('id', new class() implements BindOutStrategy {
            public function output($data)
            {
                return $data->id;
            }
        });

        $route = (new RequestRoute('/', function () {
        }))->setParent($group);

        $this->assertEquals(1, $route->bindOutParam('id', (object) ['id' => 1]));
    }

    /** @test */
    public function it_binds_params()
    {
        $this->router->bind('id', function ($id) {
            return (object) ['id' => $id];
        }, function ($data) {
            return $data->id;
        });

        $this->assertObjectHasAttribute('id', $data = $this->router->bindInParam('id', 1));

        $this->assertEquals(1, $this->router->bindOutParam('id', $data));
    }

    /** @test */
    public function it_dispatch_error_action()
    {
        $this->router->group('', null, function (GroupRoute $group) {
            $group->setErrorAction(function () {
                return 'Error';
            });

            $group->any('/', function () {
                throw new \Exception('Item not found.');
            });
        });

        $this->assertEquals('Error', $this->router->dispatch('/'));
    }

    /** @test */
    public function it_uses_host()
    {
        $this->router->setHost('example.com');

        $this->router->hidden('', 'home');

        $this->assertEquals('http://example.com/', $this->router->url('home'));
    }

    /** @test */
    public function it_adds_custom_dispatcher()
    {
        $this->router->setDispatcher(function ($action) {
            return function () use ($action) {
                return 'Call ' . $action;
            };
        });

        $this->router->group('', null, function (GroupRoute $group) {
            $group->any('/', 'index');
        });

        $this->assertEquals('Call index', $this->router->dispatch('/'));
    }

    /** @test */
    public function it_throws_if_wrong_action()
    {
        $this->router->any('/', 'index');

        $this->expectException(RoutingException::class);

        $this->router->dispatch('/');
    }

    /** @test */
    public function it_gets_matched_path()
    {
        $route = new RequestRoute($schema = '/api[/v{v}?/{vv}]', null);

        /* @var RouteData $data */
        $this->assertTrue($route->match('/api', $data));

        $this->assertEquals($schema, $route->schema());

        $this->assertInstanceOf(RouteData::class, $data);

        $this->assertEquals('/api', $data->path());

        $this->assertEquals('/api', $route->url());

        $this->assertEquals('/api', $route->url(['v' => 2]));

        $this->assertEquals('/api/v/1', $route->url(['vv' => 1]));

        $this->assertEquals('/api/v2/1', $route->url(['v' => 2, 'vv' => 1]));
    }

    /** @test */
    public function it_throws_when_param_is_required()
    {
        $this->router->any('/{v}', null, 'version');

        $this->expectException(RoutingException::class);

        $this->router->url('version');
    }

    /** @test */
    public function it_allows_regex_param()
    {
        $route = new RequestRoute('/user/{id:1|[0-9]+}', null);

        $this->assertEquals('/user/1', $route->url([]));

        $this->assertEquals('/user/2', $route->url(['id' => 2]));

        $this->expectException(RoutingException::class);

        $route->url(['id' => 'test']);
    }

    /** @test */
    public function it_gets_int_param()
    {
        $route = new RequestRoute('/{id#int}', null);

        /* @var RouteData $data */
        $this->assertTrue($route->match('/1', $data));

        $this->assertInstanceOf(RouteData::class, $data);

        $this->assertTrue(1 === $data->cleanParams('id'));

        $this->assertTrue($route->match('/-1', $data));

        $this->assertInstanceOf(RouteData::class, $data);

        $this->assertTrue(-1 === $data->cleanParams('id'));

        $this->expectException(RoutingException::class);

        $route->url(['id' => 'test']);
    }

    /** @test */
    public function it_gets_int_unsigned_param()
    {
        $route = new RequestRoute('/{id#uint}', null);

        /* @var RouteData $data */
        $this->assertTrue($route->match('/1', $data));

        $this->assertInstanceOf(RouteData::class, $data);

        $this->assertTrue(1 === $data->cleanParams('id'));

        $this->assertFalse($route->match('/-1'));
    }

    /** @test */
    public function it_gets_with_delimiter_param()
    {
        $route = new RequestRoute('/{id|*}', null);

        /* @var RouteData $data */
        $this->assertTrue($route->match('/1/1', $data));

        $this->assertInstanceOf(RouteData::class, $data);

        $this->assertTrue('1/1' === $data->cleanParams('id'));
    }

    /** @test */
    public function it_gets_boolean_param()
    {
        $route = new RequestRoute('/{id#boolean}', null);

        /* @var RouteData $data */
        $this->assertTrue($route->match('/1', $data));

        $this->assertInstanceOf(RouteData::class, $data);

        $this->assertTrue(true === $data->cleanParams('id'));

        $this->assertTrue($route->match('/0', $data));

        $this->assertInstanceOf(RouteData::class, $data);

        $this->assertTrue(false === $data->cleanParams('id'));

        $this->assertFalse($route->match('/2'));
    }

    /** @test */
    public function it_throws_on_unknown_param_type()
    {
        $route = new RequestRoute('/{id#undefined}', null);

        $this->expectException(RoutingException::class);

        $route->match('/1');
    }

    /** @test */
    public function it_throws_if_route_not_found()
    {
        $this->expectException(RoutingException::class);

        $this->router->url('undefined');
    }
}
