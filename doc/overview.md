# MinorWork Framework Overview

A minimalistic example of using MinorWork would look like this:

```php
$app = new \MinorWork\App; // Create container object

$app->setRouting($route);  // Set routing table

$app->run();               // Find matching route,
                           // run request handler,
                           // output rendered result.
```

You can install MinorWork with composer. PHP 5.4 or newer required.

```
composer require minorwork/minorwork
```

To use MinorWork in a real life situation, there are some concepts you should know:

- [Container](#container)
- [Routing](#routing)
- [Request handler](#request_handler)
- [Rendering output](#rendering_output)

If you have used other frameworks before(ex: Laravel or Slim), it's likely you already know what container and routing is about. But request handler would be a bit different from most frameworks.

There is no service provider and middleware in MinorWork.

<a name='container'></a>
## Container

Container means "you put everything into this".

`\MinorWork\App` class is the core of MinorWork, it also serve as [DI](https://www.google.com/?q=dependency+injection) container.

It works like this:

```php
$app = new App;

// Set class name
$app->set('dataLoader', '\Data\Loader\Class');  // Class name of given item.
$app->dataLoader = '\Data\Loader\Class';        // This also works

// Get value from container
$dataLoader = $app->get('dataLoader');          // An object of your data loader class
$dataLoader = $app->dataLoader;                 // This also works.
```

For detail, check [container](container.md) document.

<a name='routing'></a>
## Routing

Routing means "decide what logic to run according to URL".

A simple routing table (collection of routes) looks like this:

```php
$routing = [
    'login' => [
        'GET',                    // Method
        '/login',                 // url pattern
        '\Controller\User:login',  // Handler
    ],
    'user'    => ['/u/{id}', '\Controller\User:profile'],
    'article' => ['/a/{id}', '\Controller\Article:display'],
];
```

A route would include:

- Name of that route (key of that route)
- HTTP Method (default: `['GET', 'POST']`)
- URL pattern (default: name of that route)
- Request Handler

There are also pre-defined routes:

- `default` route is used when no other route is matched. This is the default 404 handler.
- `defaultError` route is called when your application throws an exception. This is the default error handler.

You can override pre-defined route by defining a route of the same name.

For detail, check [routing](routing.md) document.

<a name='request_handler'></a>
## Request handler

Request handler is the logic to run when route matches, it should also tell the fromework what the user should see.

A request handler can be either:

- A controller/method pair string (recommended).
  - Ex: `'\Controller\User:login'`
- A PHP [callable](http://php.net/manual/en/language.types.callable.php), like name of a function, or a closure, or other callable type.

When called, request handler will be given three inputs:

- The container itself.
- URL parameter matched in router
  - For url pattern `/user/{id:\d+}[/{name}]`, `$params` for `/user/42` would be `['id' => 42]`
- Return value of previous request handler, or null if there is no previous handler.

A simple request handler looks likes this:

```php
function basicHandler($app, $params, $prev) {
    $app->view->prepare([
        'params' => $params,
        '$prev' => $prev,
    ]);
}
```

For detail, check [request handler](request_handler.md) document.

<a name='rendering_output'></a>
## Rendering Output

To output, you can set a string into `$app->view`, or use a view handler(anything that can cast to string will do).

You should not do echo anything in request handler.
MinorWork only render view after finish execute request handler. So you can change the content any time.

MinorWork comes with two simple template engine.

- `\MinorWork\View\SimpleView` is a `str_replace` based engine. This is the default engine.
- `\MinorWork\View\JsonView` is a `json_encode` based JSON renderer.

For detail, check [view](view.md) document.

<a name='helper'></a>
## Helper

MinorWork also comes with some helper function.

`$app->routePath($routeName, $params = [], $query = [])` returns path to route of given name. `$param` will be used to generate that path. if `$query` is provided, it will be used as query string parameter (ex: `/api/users/123?format=json`).  It throws exception if failed to generate that path.

`$app->routeFullPath($routeName, $params = [], $query = [])` does almost the same thing as `routePath()`, but it also includes request schema and host name in it's output (ex: `https://example.com/api/users/123?format=json`)

`$app->redirectTo($routeName, $params = [], $query = [])` redirect you to the same path `routePath()` gives you, and terminate current request.


