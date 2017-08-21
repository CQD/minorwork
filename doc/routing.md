# Routing

You can config routing by passing an array of route config (a.k.a the routing table) to `App` object. Key of each route is the name that route.

A simple routing table looks like this:

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

## HTTP Method 
First element is allowed http method. Can either be a string (only one method allowed, ex: `GET`)
or a array of strings (multiple methods allowed, ex: `['GET', 'POST']`)

Method can be omitted. If so, `['GET', 'POST']` will be used.

```php
// This
'login' => ['/login', '\Controller\User:login']

// equals to this
'login' => [['GET', 'POST'], '/login', '\Controller\User:login']
```

## URL pattern
Second element is the URL pattern to match. URL pattern uses [fast-route's syntax](https://github.com/nikic/FastRoute#defining-routes):

- `/user` Matches only /user
- `/user/{id:\d+}` Matches /user/42 and /user/53, but not /user and /user/xyz
- `/user/{name}` Matches /user/foobar, but not /user/foo/bar
- `/user/{name:.+}` Matches /user/foo/bar as well
- `/user/{id:\d+}[/{name}]` Matches /user/42 AND /user/42/xyz
- `/user[/{id:\d+}[/{name}]]` Option parts can be nested

URL pattern can also be omitted, if so, route name will be used.

```php
// This
'login' => ['\Controller\User:login']

// equals to this
'login' => ['/login', '\Controller\User:login']

// also equals to this
'login' => [['GET', 'POST'], '/login', '\Controller\User:login']
```

## Request Handler

The third element is the request handler. You can set a single handler, or set an array of handlers that will be executed one after one in given order.

Read [Request handler](request_handler.md) document for more detail.

You **CAN NOT** omit request handler in a route (why define a route that no one cares?)

### Default routes

There are also pre-defined routes:

- `default` route is used when no other route is matched. This is the default not found handler (404).
- `defaultError` route is called when your application throws an exception. This is the default error handler (500).

You can override pre-defined route by defining a route of the same name.

