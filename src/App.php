<?php

namespace MinorWork;

use FastRoute\RouteParser\Std as RouteParserStd;

/**
 * Entry point for an application
 */
class App
{
    private $itemSetting = [];
    private $items = [];

    private $routings = null;
    private $router = null;

    private $dispatcher = null;

    /**
     * Get an item from container
     * @param string $name name for desired item
     */
    public function get($name)
    {
        if (isset($this->items[$name])) {
            return $this->items[$name];
        }

        if (!isset($this->itemSetting[$name])) {
            return null;
        }

        $itemSetting = $this->itemSetting[$name];
        if (is_callable($itemSetting)) {
            $item = $itemSetting();
        } elseif (is_string($itemSetting) && class_exists($itemSetting)) {
            $item = new $itemSetting;
        } else {
            $item = $itemSetting;
        }

        $this->items[$name] = $item;
        return $item;
    }

    /**
     * Set one or multiple items, or factory function of items, or class of items, into DI container.
     */
    public function set($name, $value = null)
    {
        $values = is_array($name) ? $name : [$name => $value];
        foreach ($values as $name => $value) {
            $this->itemSetting[$name] = $value;
        }
    }

    /**
     * Set app routing.
     *
     * TODO group routing support
     */
    public function setRouting(array $routings = [])
    {
        $this->dispatcher = null;
        $this->routings = $routings + [
            'default' => ['*', '*', function($app, $params){
                http_response_code(404);
                echo "What a lovely 404!";
            }],
        ];

        foreach ($this->routings as $name => &$routing) {
            if (1 == count($routing)) {
                array_unshift($routing, '/' . ltrim($name, '/'));
            }
            if (2 == count($routing)) {
                array_unshift($routing, ['GET', 'POST']);
            }
        }
        unset($routing);
    }

    /**
     * Determin which route handler to use and parameters in uri.
     *
     * @param string $method HTTP Method used
     * @param string $uri request path, without query string
     */
    public function route($method, $uri)
    {
        $routings = $this->routings;
        $this->dispatcher = $this->dispatcher ?: \FastRoute\simpleDispatcher(function(\FastRoute\RouteCollector $r) use ($routings) {
            foreach ($routings as $routing) {
                // 'GET', '/user/{id:\d+}', $handler
                $r->addRoute($routing[0], $routing[1], $routing[2]);
            }
        });

        $routeInfo = $this->dispatcher->dispatch($method, $uri);

        if (\FastRoute\Dispatcher::FOUND === $routeInfo[0]) {
            return array_slice($routeInfo, 1);
        }

        return null;
    }

    public function redirectTo($routeName, $params = [], $query = [])
    {
        header(sprintf("Location: %s", $this->routePath($routeName, $params, $query)));
        exit;
    }

    public function routePath($routeName, $params = [], $query = [])
    {
        if (!isset($this->routings[$routeName])) {
            throw new \Exception("Route name '{$routeName}' not found!");
        }

        $pattern = $this->routings[$routeName][1];
        $path = $this->patternToPath($pattern, $params);
        if ($query) {
            $query .= "?" . http_build_query($query);
        }

        return $path;
    }

    /**
     * Convert url pattern to actual path
     */
    private function patternToPath($pattern, $params)
    {
        // Inspired by https://github.com/nikic/FastRoute/issues/66
        // but uses named params, don't care param order and size
        static $routeParser = null;
        $routeParser = $routeParser ?: new RouteParserStd;
        $routes = $routeParser->parse($pattern);

        $resultUrl = null;
        foreach ($routes as $route) {
            $url = '';
            $paramMissing = false;
            foreach ($route as $part) {
                // Fixed segment in the route
                if (is_string($part)) {
                    $url .= $part;
                    continue;
                }

                // Placeholder in the route
                $name = $part[0];
                if (!isset($params[$name])) {
                    $paramMissing = true;
                    break;
                }
                $url .= $params[$name];
            }

            // all param match, this url is valid
            if (!$paramMissing) {
                $resultUrl = $url;
            }
        }

        if ($resultUrl) {
            return $resultUrl;
        }
        throw new \Exception(sprintf("Can not generate path for '%s' using params: %s.", $pattern, json_encode($params)));
    }

    /**
     * Entry point for application
     */
    public function run($options = [])
    {
        $this->set([
            '_GET' => $_GET,
            '_POST' => $_POST,
            '_SERVER' => $_SERVER,
        ]);

        $method = @$options['method'] ?: $_SERVER['REQUEST_METHOD'];

        $uri = @$options['uri'] ?: rawurldecode($_SERVER['REQUEST_URI']);
        if (false !== $pos = strpos($uri, '?')) {
            $uri = substr($uri, 0, $pos);
        }

        if (!$this->routings) {
            $this->setRouting(); // set default routing
        }
        $routeInfo = $this->route($method, $uri);
        if (!$routeInfo) {
            $routeInfo = [
                $this->routings['default'][2],
                [],
            ];
        }

        list($handler, $params) = $routeInfo;
        $this->executeHandler($handler, $params);
    }

    /**
     * Handle current request using specificed route handler
     */
    public function runAs($routeName, $params = [])
    {
        if (!isset($this->routings[$routeName])) {
            throw new \Exception("Route name '{$routeName}' not found!");
        }

        $routeInfo = $this->route($method, $uri);

        list($handler, $params) = $routeInfo;
        $this->executeHandler($handler, $params);
    }

    /**
     * Parse and execute request handler
     */
    public function executeHandler($handler, $params)
    {
        // a function(-ish) thing can be called
        if (is_callable($handler)) {
            $handler($this, $params);
            return;
        }

        // only callable and controller/method pair (`:` seprated string) can be accepted
        if (!is_string($handler)) {
            $type = gettype($handler);
            throw new \Exception("'{$type}' can not be used as handler, should be a callable or controller/method pair!");
        }

        if (false === $pos = strpos($handler, ':')) {
            throw new \Exception("Can not parse '{$handler}' for corresponding controller/method!");
        }

        // controller/method pair
        list($class, $method) = explode(':', $handler, 2);
        $controller = new $class;
        $controller->$method($this, $params);
    }

}
