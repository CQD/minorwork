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
            list($parsedHandler, $parsedParams) = $routeInfo;
            $expectedHandler = end($routes[$name]);
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

    private function routes()
    {
        return [
            'a' => ['/a/{paramA}/{paramB}', 'intval'],
            'b' => ['/b/{paramA}[/{paramB}[/{paramC}]]', 'strval'],
            'c' => ['/basic/path', 'isset'],
            'd' => ['is_array'],
        ];
    }
}
