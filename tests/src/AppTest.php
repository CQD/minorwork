<?php
namespace MinorWork;

use PHPUnit\Framework\TestCase;

class AppTest extends TestCase
{
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
            [$routes, null, ['paramB' => 'XY'], '/not/exists'],
        ];
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

    private function routes()
    {
        return [
            'a' => ['/a/{paramA}/{paramB}', 'intval'],
            'b' => ['/b/{paramA}[/{paramB}[/{paramC}]]', 'strval'],
            'c' => ['/basic/path', 'isset'],
            'd' => ['is_array'],
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
