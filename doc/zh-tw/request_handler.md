# Request handler

Request Handler 是執行邏輯的地方。設定 Request Handler 的方式有下列幾種：

- controller/method 字串（建議作法）
  - `'\Controller\User:login'` 的意思是 `$obj = new \Controller\User(); $obj->login($app, $params)`
- PHP [callable](http://php.net/manual/en/language.types.callable.php)。例如函式名稱、或 closure、或其他的 callable 類別。
  - `'loginHandler'` 表示要呼叫 `loginHandler($app, $params)`

被呼叫到的時候， request handler 會收到三個參數：

- 容器物件
- router 對到的網址參數
  - 網址格式如果是 `/user/{id:\d+}[/{name}]`，對於 `/user/42` 來說 `$params` 會是 `['id' => 42]`
- 前一個 handler 得回傳值，如果沒有「前一個 handler」，那會是 null。請參照 Handler Queue 的說明。

簡單的 handler 大概會長這樣：

```php
function basicHandler($app, $params, $prev) {
    $app->view->prepare([
        'params' => $params,
        '$prev' => $prev,
    ]);
}
```

### Handler 佇列與 Middleware

一個 request 可以對應多個 request handler。 MinorWork 內部會記錄一份 handler 佇列。

```php
[
'singlehandler' => ['/api/users/{id}', '\Users:porfileJson'],
'multihandler'  => ['/api/users/{id}', ['\Users:porfile', 'toJson']],
]
```

有多個 request handler 的時候， handler 收到的第三個參數會是前一個 handler 的回傳值。第一個被執行的 handler 因為沒有「前一個 handler」，會拿到 null。

```php
'multihandler' => ['/api/users/{id}', [
    function($app, $params) {
        // 回傳使用者資料陣列
        return User::find($params['id'])->toArray();
    },
    function($app, $params, $user) {
        // 在收到的使用者資料裡面加料
        $user['is_hungry'] = (lastMealTime($user['id']) - time() > 28800);
        return $user;
    },
    function($app, $params, $arrayData) {
        // 輸出 json
        $app->view = json_encode($arrayData);
    },
]],
```

Handler 佇列可以在執行的時候即時變更。你可以打斷執行到一半的 handler 佇列，或在佇列裡面加上新的項目。

- `$app->stop()` 會清空 handler 佇列（打斷原本的執行）
- `$app->appendHandlerQueue()` 會在佇列後面加上新的 handler（如果是陣列，會全部加入）
- `$app->preppendHandlerQueue()` 會在佇列開頭加上新的 handler（如果是陣列，會全部加入）

```php
$app = new App();
$app->setRouting([
    'multihandler' => [[
        function($a, $p, $po) {},            // 會執行
        function($a, $p, $po) {$a->stop();}, // 會執行
        function($a, $p, $po) {},            // 不會執行
    ]],
]);
$app->run();
```

MinorWork 處理 middleware 的方式就是多重 request handler。你可以在多個 route 裡面重用同一份 handler。

例如，你可能會想要在某些頁面檢查使用者是否登入，如果沒登入的話，用 `$app->redirectTo('login')` 把他們送到登入畫面。

```php
$route = [
    'article' => ['/a/{id}',      ['checkAuth', '\Article:show']],
    'msg'     => ['/my/messages', ['checkAuth', '\Profile:showMessage']],
    'login'   => ['/login',       '\Login:login'],
];
```

### Handler 別名以及 Class Prefix

你可以用 `$app->handlerAlias()` 為 handler 設定別名。例如：

```php
$app->handlerAlias([
    'CheckLogin' => '\Handler\Validation:checkLogin',
    'logArticleView' => function($app, $param, $prev){
        // 在這裡寫 log
    },
]);

$app->setRouting([
    'article' => ['/a/{id}',      ['checkAuth', '\Article:show', 'logPageView']],
    'msg'     => ['/my/messages', ['checkAuth', '\Profile:showMessage']],
    'login'   => ['/login',       '\Login:login'],
]);
```

