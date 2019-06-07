# Request handler

A request handler can be either:

- A controller/method pair string (recommended).
  - `'\Controller\User:login'` means `$obj = new \Controller\User(); $obj->login($app, $params)`
- A PHP [callable](http://php.net/manual/en/language.types.callable.php), like name of a function, or a closure, or other callable type.
  - `'loginHandler'` means calling `loginHandler($app, $params)`

When called, request handler will be given three inputs:

- The container itself.
- URL parameter matched in router
  - For url pattern `/user/{id:\d+}[/{name}]`, `$params` for `/user/42` would be `['id' => 42]`
  - Return value of previous request handler, or null if there is no previous handler. Read Handler Queue section for detail.

A simple request handler looks likes this:

```php
function basicHandler($app, $params, $prev) {
    $app->view->prepare([
        'params' => $params,
        '$prev' => $prev,
    ]);
}
```

### Handler Queue and Middleware

For each request there can be multiple request handler. MinorWork maintains a handler queue internally.

```php
[
'singlehandler' => ['/api/users/{id}', '\Users:porfileJson'],
'multihandler'  => ['/api/users/{id}', ['\Users:porfile', 'toJson']],
]
```

When there are multiple request handlers, a third argument will be passed to request handler. The first handler executed will receive `null`, following handlers will receive return value of previous handler.

```php
'multihandler' => ['/api/users/{id}', [
    function($app, $params) {
        // return user data as array
        return User::find($params['id'])->toArray();
    },
    function($app, $params, $user) {
        // $user data received, now add more data to it
        $user['is_hungry'] = (lastMealTime($user['id']) - time() > 28800);
        return $user;
    },
    function($app, $params, $arrayData) {
        // render output as json
        $app->view->prepare(json_encode($arrayData));
    },
]],
```

Handler queue can be altered at the runtime, you can break current handler chain, or add new handler to current handler queue:

- `$app->stop()` clears handler queue.
- `$app->appendHandlerQueue()` add a request handler (or an array of handlers) to the end of handler queue
- `$app->prependHandlerQueue()` add a request handler (or an array of handlers) to the beginning of handler queue
  - This function is also aliased to `$app->next()`.

```php
$app = new App();
$app->setRouting([
    'multihandler' => [[
        function($a, $p, $po) {},            // will execute
        function($a, $p, $po) {$a->stop();}, // will execute
        function($a, $p, $po) {},            // will not execute
    ]],
]);
$app->run();
```

Setting multiple request handlers is MinorWork's way to do middleware. You can reuse handler in many routes.

For example, you may want to make a auth handler that check if user has logged in, if and redirect to login page using `$app->redirectTo('login')` when need.

```php
$route = [
    'article' => ['/a/{id}',      ['checkAuth', '\Article:show']],
    'msg'     => ['/my/messages', ['checkAuth', '\Profile:showMessage']],
    'login'   => ['/login',       '\Login:login'],
];
```

### Handler Alias and Class Prefix

You can use `$app->handlerAlias()` to set alias name for a handler. For example:

```php
$app->handlerAlias([
    'CheckLogin' => '\Handler\Validation:checkLogin',
    'logArticleView' => function($app, $param, $prev){
        // do some logging here
    },
]);

$app->setRouting([
    'article' => ['/a/{id}',      ['checkAuth', '\Article:show', 'logPageView']],
    'msg'     => ['/my/messages', ['checkAuth', '\Profile:showMessage']],
    'login'   => ['/login',       '\Login:login'],
]);
```

