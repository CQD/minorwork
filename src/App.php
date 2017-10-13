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

    private $handlerAlias = [];
    private $handlerQueue = [];

    public function __construct($items = [])
    {
        // Default container item
        $this->set($items + [
            '_GET' => $_GET,
            '_POST' => $_POST,
            '_SERVER' => $_SERVER,
            'view' => '\MinorWork\View\SimpleView',
            'session' => '\MinorWork\Session\NativeSession',
        ]);

        // check subfolder
        $scriptName = @$this->get('_SERVER')['SCRIPT_NAME'];
        $this->items['___.baseDir'] = dirname($scriptName) ?: '/';

        // Default routings
        $this->routings = [
            'default' => ['*', '*', function($app, $params){
                http_response_code(404);
                $app->set('view', '404! I can not find what you want!');
            }],
            'defaultError' => ['!', '!', function ($app, $params) {
                syslog(LOG_ERR, end($params));
                http_response_code(500);
                $app->set('view', 'Oops, some thing is wrong!');
            }],
        ];
    }

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

    public function __get($name)
    {
        return $this->get($name);
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

        unset($this->items[$name]);
    }

    public function __set($name, $value)
    {
        return $this->set($name, $value);
    }

    /**
     * Set app routing.
     *
     * TODO group routing support
     */
    public function setRouting(array $routings = [])
    {
        $this->dispatcher = null;
        $this->routings = $routings + $this->routings;

        foreach ($this->routings as $name => &$routing) {
            if (1 == count($routing)) {
                array_unshift($routing, '/' . ltrim($name, '/'));
            }
            if (2 == count($routing)) {
                array_unshift($routing, ['GET', 'POST']);
            }
        }
    }

    /**
     * Set alias name for request handlers
     */
    public function handlerAlias($name, $value = null)
    {
        $alias = is_array($name) ? $name : [$name => $value];
        $this->handlerAlias = $alias + $this->handlerAlias;
    }

    /**
     * Stop excuting next request handler
     */
    public function stop()
    {
        $this->handlerQueue = [];
    }

    /**
     * Determin which route to use and parameters in uri.
     *
     * @param string $method HTTP Method used
     * @param string $uri request path, without query string
     * @return array|null [$routeName, $params]
     */
    public function route($method, $uri)
    {
        // Remove subfolder from URI if needed
        $baseDir = $this->get('___.baseDir');
        if ("/" !== $baseDir && 0 === strpos($uri, $baseDir)) {
            $uri = substr($uri, strlen($baseDir));
        }

        // Check which route is being used.
        $routings = $this->routings;
        $this->dispatcher = $this->dispatcher ?: \FastRoute\simpleDispatcher(function(\FastRoute\RouteCollector $r) use ($routings) {
            foreach ($routings as $name => $routing) {
                // 'GET', '/user/{id:\d+}', $routeName
                $r->addRoute($routing[0], $routing[1], $name);
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
        $this->stop();
    }

    public function routePath($routeName, $params = [], $query = [])
    {
        if (!isset($this->routings[$routeName])) {
            throw new \Exception("Route name '{$routeName}' not found!");
        }

        $pattern = $this->routings[$routeName][1];
        $path = $this->patternToPath($pattern, $params);
        if ($query) {
            $path .= "?" . http_build_query($query);
        }

        $path = rtrim($this->get('___.baseDir'), '/') . $path;

        return $path;
    }

    public function routeFullPath($routeName, $params = [], $query = [])
    {
        $isHttps = @$_SERVER['HTTPS'] && ('off' !== $_SERVER['HTTPS']);
        $schema = $isHttps ? "https://" : "http://";
        $host = @$_SERVER['HTTP_HOST'] ?: 'localhost';
        return $schema . $host . $this->routePath($routeName, $params, $query);
    }

    public function staticPath($path)
    {
        return rtrim($this->get('___.baseDir'), '/') . $path;
    }

    public function staticFullPath($path)
    {
        $isHttps = @$_SERVER['HTTPS'] && ('off' !== $_SERVER['HTTPS']);
        $schema = $isHttps ? "https://" : "http://";
        $host = @$_SERVER['HTTP_HOST'] ?: 'localhost';
        return $schema . $host . $this->staticPath($path);
    }
    /**
     * Convert url pattern to actual path
     */
    private function patternToPath($pattern, $params)
    {
        // Inspired by https://github.com/nikic/FastRoute/issues/66
        // but uses named params, don't care param order and size
        $routeParser = new RouteParserStd;
        $routes = $routeParser->parse($pattern);

        // check from the longest route to the shortest route
        // first (longest) full match is the route we need
        for ($i = count($routes) - 1; $i >= 0; $i--) {
            $url = '';
            foreach ($routes[$i] as $part) {
                // replace placeholder to actual value
                if (is_array($part)) {
                    $part = @$params[$part[0]];
                }

                // if route contains part not defined in $params, abandan this route
                if (null === $part) {
                    continue 2;
                }
                $url .= $part;
            }

            // first full match, this is our hero url
            return $url;
        }

        // no full match, throw Exception
        throw new \Exception(sprintf("Can't make path for '%s' with param: %s.", $pattern, json_encode($params)));
    }

    /**
     * Entry point for application
     */
    public function run($options = [])
    {
        $method = @$options['method'] ?: $_SERVER['REQUEST_METHOD'];

        $uri = @$options['uri'] ?: rawurldecode($_SERVER['REQUEST_URI']);
        if (false !== $pos = strpos($uri, '?')) {
            $uri = substr($uri, 0, $pos);
        }

        $routeInfo = $this->route($method, $uri);
        if (!$routeInfo) {
            $routeInfo = ['default', []];
        }

        list($routeName, $params) = $routeInfo;

        try {
            return $this->runAs($routeName, $params);
        } catch (\Exception $e) {
            $params[] = $e;
            return $this->runAs('defaultError', $params);
        }
    }

    /**
     * Handle current request using specificed route handler
     */
    public function runAs($routeName, $params = [])
    {
        if (!isset($this->routings[$routeName])) {
            throw new \Exception("Route name '{$routeName}' not found!");
        }

        // populate handler queue
        $handler = end($this->routings[$routeName]);
        $this->appendHandlerQueue($handler);

        // Run everything in handler queue
        $output = null;
        while ($handler = array_shift($this->handlerQueue)) {
            $output = $this->executeHandler($handler, $params, $output);
        }
        echo $this->get('view');

        return $output;
    }

    /**
     * pupolate handler queue from the end
     */
    public function appendHandlerQueue($handler)
    {
        $this->addToHandlerQueue($handler, 'end');
    }

    /**
     * pupolate handler queue from the end
     */
    public function prependHandlerQueue($handler)
    {
        $this->addToHandlerQueue($handler, 'head');
    }

    private function addToHandlerQueue($handler, $order)
    {
        $isArrayOfHandler = (!is_callable($handler) && is_array($handler));
        $handlers = $isArrayOfHandler ? $handler : [$handler];

        if ('end' === $order) {
            foreach ($handlers as $handler) {
                $this->handlerQueue[] = $handler;
            }
        } else {
            foreach (array_reverse($handlers) as $handler) {
                array_unshift($this->handlerQueue, $handler);
            }
        }
    }

    /**
     * Parse and execute request handler
     */
    public function executeHandler($handler, $params, $prevOutput = null)
    {
        // a function(-ish) thing which can be called
        if (is_callable($handler)) {
            return $handler($this, $params, $prevOutput);
        }

        // aliased handler
        if ($actualHandler = @$this->handlerAlias[$handler]) {
            return $this->executeHandler($actualHandler, $params, $prevOutput);
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
        $class = $this->handlerClassPrefix ? "{$this->handlerClassPrefix}{$class}" : $class;
        $controller = new $class;
        return $controller->$method($this, $params, $prevOutput);
    }
}
