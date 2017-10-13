<?php
namespace MinorWork;

use PHPUnit\Framework\TestCase;
use PHPUnit\Runner\Version as PHPUnitVersion;

class AppTest extends TestCase
{
    public function setUp()
    {
        $_SERVER['SCRIPT_NAME'] = '/index.php';
        $_SERVER['HTTP_HOST'] = 'example.com';
        $_SERVER['HTTPS'] = 'off';
    }

    public function testCreationApp()
    {
        $app = new App;
        $this->assertInstanceOf('\MinorWork\Session\NativeSession', $app->session);
        $this->assertInstanceOf('\MinorWork\View\SimpleView', $app->view);
        $this->assertNull($app->notExistingField);

        $app = new App([
            'view' => '\MinorWork\View\JsonView',
            'newField' => 'Some Text',
        ]);
        $this->assertInstanceOf('\MinorWork\Session\NativeSession', $app->session);
        $this->assertInstanceOf('\MinorWork\View\JsonView', $app->view);
        $this->assertEquals('Some Text', $app->newField);
        $this->assertNull($app->notExistingField);
    }

    public function testSet()
    {
        $app = new App;

        // test factory function
        $invokeCnt = 0;
        $factory = function() use (&$invokeCnt) {
            $invokeCnt++;
            return ['A' => 4, 'B' => 2];
        };

        $app->a = $factory;
        $this->assertEquals(['A' => 4, 'B' => 2], $app->a);
        $this->assertEquals(['A' => 4, 'B' => 2], $app->a);

        $this->assertEquals(1, $invokeCnt, 'Factory function should only be called once');

        // test class name
        $app->b = '\stdClass';
        $this->assertInstanceOf('\stdClass', $app->b);

        // test simple set
        $app->c = 'MIEWMIEWMIEW';
        $this->assertEquals('MIEWMIEWMIEW', $app->c);

        // test override item
        $app->c = 'HAHAHA';
        $this->assertEquals('HAHAHA', $app->c);
    }

    public function testHandlerAlias()
    {
        $aCalled = false;

        $app = new App();
        $app->handlerAlias(['a' => function () use (&$aCalled) { $aCalled = true;},]);
        $app->setRouting(['routea' => ['a']]);
        $app->runAs('routea');

        $this->assertTrue($aCalled);
    }

    /**
     * @dataProvider routeProvider
     */
    public function testRoute($routes, $name, $expectedParams, $path)
    {
        if (null === $path) {
            return;
        }

        $app = new App();
        $app->setRouting($routes);

        $routeInfo = $app->route('GET', $path);
        if (null === $name) {
            $this->assertNull($routeInfo);
        } else {
            list($parsedName, $parsedParams) = $routeInfo;
            $expectedHandler = end($routes[$name]);
            $parsedHandler = end($routes[$parsedName]);
            $this->assertEquals($parsedName, $name);
            $this->assertEquals($parsedParams, $expectedParams);
            $this->assertEquals($parsedHandler, $expectedHandler);
        }
    }

    public function routeProvider()
    {
        $routes = $this->routes();
        return [
            [$routes, 'a', ['paramB' => 'YX', 'paramA' => 'XY'], '/a/XY/YX'],
            [$routes, 'b', ['paramB' => 'YX', 'paramA' => 'XY', 'paramC' => 'C'], '/b/XY/YX/C'],
            [$routes, 'b', ['paramA' => 'XY'], '/b/XY'],
            [$routes, 'd', [], '/d'],
            [$routes, 'slashhell', ['a' => 'A', 'b' => '42'], '///////42///A'],
            [$routes, null, ['paramB' => 'XY'], '/not/exists'],
        ];
    }

    public function routeProviderWithQueryString()
    {
        $routes = $this->routeProvider();
        foreach ($routes as &$route) {
            $query = ['query' => 'string', 'route' => $route[1] ?: 'null'];
            $route[3] .= '?' . http_build_query($query);
            $route[] = $query;
        }
        return $routes;
    }

    /**
     */
    public function testMultipleHandler()
    {
        $app = new App();
        $app->setRouting($this->routes());

        $params = ['a' => 42, 'B' => 'John'];

        ob_start();
        $app->runAs('multihandler', $params);
        $output = ob_get_clean();

        $this->assertTrue($app->handler1, "1st handler should execute.");
        $this->assertEquals($params, $app->handlerParams1);
        $this->assertEquals(null, $app->handlerPrevOutput1);

        $this->assertTrue($app->handler2, "2nd handler should execute.");
        $this->assertEquals($params, $app->handlerParams2);
        $this->assertEquals(['a' => 4], $app->handlerPrevOutput2);

        $this->assertTrue($app->handler3, "3rd handler should execute.");
        $this->assertEquals($params, $app->handlerParams3);
        $this->assertEquals(['a' => 4, 'b' => 2], $app->handlerPrevOutput3);

        $this->assertEquals('{"a":4,"b":2}', $output, "Should render json");
    }

    public function testRunAs()
    {
        $aCalled = false;

        $app = new App();
        $app->setRouting(['a' => [function() use (&$aCalled) {$aCalled = true;}]]);

        $app->runAs('a');
        $this->assertTrue($aCalled);

        if (method_exists($this, 'expectException')) {
            $this->expectException('\Exception');
            $app->runAs('non_exists_route');
        }
    }

    /**
     * @dataProvider routeProvider
     * @dataProvider routeProviderWithQueryString
     */
    public function testRun($routes, $name, $expectedParams, $path)
    {
        $app = new App();
        $app->setRouting($routes);

        ob_start();
        $parsedParams = $app->run([
            'method' => 'GET',
            'uri' => $path,
        ]);
        ob_end_clean();

        if (null === $name) {
            $this->assertNull($parsedParams);
            $this->assertEquals(404, http_response_code(), "No match found, default handler should send 404 status code");
        } else {
            $this->assertEquals($parsedParams, $expectedParams);
        }
    }

    /**
     * @dataProvider routeProvider
     * @dataProvider routeProviderWithQueryString
     * @dataProvider badRoutePathProvider
     */
    public function testRoutePath($routes, $name, $params, $expectedPath, $query = [])
    {
        $app = new App();
        $app->setRouting($routes);

        if (!method_exists($this, 'expectException') && (null === $name || null === $expectedPath)) {
            $this->markTestSkipped('expectException() not available.');
            return;
        }

        if (null === $name) {
            $name = 'not_exist_route';
            $this->expectException('\Exception', "Throw exception when route does not exists.");
        }

        if (null === $expectedPath) {
            $this->expectException('\Exception', "Throw exception when did not provide enough params to creath actual path");
        }

        $actualPath = $app->routePath($name, $params, $query);
        $this->assertEquals($expectedPath, $actualPath);

        $httpFullPath = "http://example.com{$actualPath}";
        $httpsFullPath = "https://example.com{$actualPath}";

        $_SERVER['HTTPS'] = false;
        $this->assertEquals($httpFullPath, $app->routeFullPath($name, $params, $query));

        $_SERVER['HTTPS'] = 'off';
        $this->assertEquals($httpFullPath, $app->routeFullPath($name, $params, $query));

        $_SERVER['HTTPS'] = 'on';
        $this->assertEquals($httpsFullPath, $app->routeFullPath($name, $params, $query));
    }

    public function badRoutePathProvider()
    {
        $routes = $this->routes();
        return [
            [$routes, 'a', [], null, []],
        ];
    }

    public function testStaticPath()
    {
        $app = new App();
        $this->assertEquals('/a.js', $app->staticPath('/a.js'));
        $this->assertEquals('http://example.com/a.js', $app->staticFullPath('/a.js'));

        $_SERVER['SCRIPT_NAME'] = '/subfolder/index.php';
        $app = new App();
        $this->assertEquals('/subfolder/a.js', $app->staticPath('/a.js'));
        $this->assertEquals('http://example.com/subfolder/a.js', $app->staticFullPath('/a.js'));
    }

    /**
     * @dataProvider routeProvider
     * @dataProvider routeProviderWithQueryString
     * @dataProvider badRedirectToProvider
     */
    public function testRedirectTo($routes, $name, $params, $expectedPath, $query = [])
    {
        if (!method_exists($this, 'expectException') && (null === $name || null === $expectedPath)) {
            $this->markTestSkipped('expectException() not available.');
            return;
        }

        if (null === $name) {
            $name = 'not_exist_route';
            $this->expectException('\Exception', "Throw exception when route does not exists.");
        }

        if (null === $expectedPath) {
            $this->expectException('\Exception', "Throw exception when did not provide enough params to creath actual path");
        }

        $app = new App();
        $app->setRouting($routes);

        $app->redirectTo($name, $params, $query);
        $this->assertContains("Location: {$expectedPath}", getHeaders());
        header_remove();
    }

    public function badRedirectToProvider()
    {
        $routes = $this->routes();
        return [
            [$routes, 'a', [], null, []],
        ];
    }

    /**
     * @dataProvider executeHandlerProvider
     */
    public function testExecuteHandler($alias, $handler, $excpectedOutput, $shouldThrowException)
    {
        $app = new App();
        $app->handlerAlias($alias);


        if (!method_exists($this, 'expectException') && $shouldThrowException) {
            $this->markTestSkipped('expectException() not available.');
            return;
        }

        if ($shouldThrowException) {
            $this->expectException('\Exception');
        }

        $output = $app->executeHandler($handler, []);
        $this->assertEquals($excpectedOutput, $output);
    }

    public function executeHandlerProvider()
    {
        $alias = ['a' => '\MinorWork\MockController:sw'];
        return [
            // [alias list, handler, return value, throws exception]
            [$alias, function(){return 'Wonderful';}, 'Wonderful', false],
            [$alias, '\MinorWork\MockController:sw', 'Star Wars', false],
            [$alias, '\MinorWork\MockController:st', 'Star Trek', false],
            [$alias, 'a', 'Star Wars', null],
            [$alias, 'b', null, true],
            [$alias, new \stdClass, null, true],
        ];
    }

    public function testHandlerClassPrefix()
    {
        $app = new App();
        $app->handlerClassPrefix = "\MinorWork\\";
        $app->setRouting([
            'sw' => ['MockController:sw'],
            'st' => ['MockController:st'],
        ]);

        $this->assertEquals('Star Wars', $app->runAs('sw'));
        $this->assertEquals('Star Trek', $app->runAs('st'));
    }

    public function testDefaultErrorHandler()
    {
        $app = new App();
        $app->setRouting([
            'iWillFail' => [function($app, $params){throw new \Exception("Ha");}],
        ]);

        $options = [
            'method' => 'GET',
            'uri' => '/iWillFail',
        ];
        ob_start();
        $app->run($options);
        $output = ob_get_contents();
        ob_end_clean();

        $this->assertEquals('Oops, some thing is wrong!', $output);
        $this->assertEquals(500, http_response_code());
    }

    public function testHandlerQueue()
    {
        $app = new App();
        $app->handlerAlias('A', function(){echo 'a';});
        $app->handlerAlias('B', function(){echo 'b';});
        $app->handlerAlias('C', function(){echo 'c';});
        $app->handlerAlias('D', function(){echo 'd';});
        $app->handlerAlias('PA', function($app){$app->prependHandlerQueue('A');});
        $app->handlerAlias('PB', function($app){$app->prependHandlerQueue('B');});
        $app->handlerAlias('PC', function($app){$app->prependHandlerQueue('C');});
        $app->handlerAlias('PD', function($app){$app->prependHandlerQueue('D');});
        $app->handlerAlias('AA', function($app){$app->appendHandlerQueue('A');});
        $app->handlerAlias('AB', function($app){$app->appendHandlerQueue('B');});
        $app->handlerAlias('AC', function($app){$app->appendHandlerQueue('C');});
        $app->handlerAlias('AD', function($app){$app->appendHandlerQueue('D');});

        $custom = function($direction, $names) {
            return function($app) use ($direction, $names){
                foreach ($names as $name) {
                    $method = ('P' === $direction) ?  'prependHandlerQueue' : 'appendHandlerQueue';
                    $app->$method($name);
                }
            };
        };

        $stop = function($names = []) {
            return function($app) use ($names){
                $app->stop();
                foreach ($names as $name) {
                    $app->appendHandlerQueue($name);
                }
            };
        };

        $tests = [
            [['A', 'B', 'C', 'D'], 'abcd'],
            [['A', 'PB', 'C', 'D'], 'abcd'],
            [['A', 'AB', 'AC', 'D'], 'adbc'],
            [['A', $custom('A', ['D', 'A', 'C', 'B']), 'AC', 'D'], 'addacbc'],
            [['A', $custom('P', ['D', 'A', 'C', 'B']), 'AC', 'D'], 'abcaddc'],
            [['A', 'PB', 'AC', $stop(), 'A', 'D'], 'ab'],
            [['A', 'PB', 'AC', $stop(['B', 'C']), 'A', 'D'], 'abbc'],
        ];

        foreach ($tests as $idx => $test) {
            $app->setRouting([
                't' => [$test[0]],
            ]);
            ob_start();
            $app->runAs('t');
            $output = ob_get_contents();
            ob_end_clean();

            $no = $idx + 1;
            $this->assertEquals($test[1], $output, "Handler queue test {$no} should output `{$test[1]}`");
        }
    }

    public function testSubFolder()
    {
        $_SERVER['SCRIPT_NAME'] = '/subfolder/index.php';

        $routing = [
            'p1'      => ['/p1', 'p1:p1'],
            'default' => ['*',   'de:de'],
        ];

        $app = new App();
        $app->setRouting($routing);

        $this->assertEquals(['p1', []], $app->route('GET', '/subfolder/p1'));
        $this->assertEquals(null, $app->route('GET', '/subfolder/p11'));
        $this->assertEquals('/subfolder/p1', $app->routePath('p1'));
        $this->assertEquals('http://example.com/subfolder/p1', $app->routeFullPath('p1'));
    }

    private function routes()
    {
        $echo = function($a, $p){return $p;};
        return [
            'a' => ['/a/{paramA}/{paramB}', $echo],
            'b' => ['/b/{paramA}[/{paramB}[/{paramC}]]', $echo],
            'c' => ['/basic/path', $echo],
            'd' => [$echo],
            'slashhell' => ['///////{b:\d+}///{a}', $echo],
            'multihandler' => [[
                function($a, $p, $po) {
                    $a->handler1 = true;
                    $a->handlerParams1 = $p;
                    $a->handlerPrevOutput1 = $po;
                    return ['a'=>4];
                },
                function($a, $p, $po) {
                    $a->handler2 = true;
                    $a->handlerParams2 = $p;
                    $a->handlerPrevOutput2 = $po;
                    return $po + ['b' => 2];
                },
                function($a, $p, $po) {
                    $a->handler3 = true;
                    $a->handlerParams3 = $p;
                    $a->handlerPrevOutput3 = $po;
                    $a->view->prepare(json_encode($po));
                    $a->stop();
                },
                function($a, $p){
                    throw new \Exception("Already stopped, third handler should not be called!");
                },
            ]],
        ];
    }
}

class MockController
{
    public function st(){ return "Star Trek";}
    public function sw(){ return "Star Wars";}
}
