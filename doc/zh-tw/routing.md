# Routing

要設定 routing，你需要把包含 route 設定的陣列（或稱為 routing 表）設定給 `App` 物件 。

簡單的 routing 表長得像這樣：

```php
$routing = [
    'login' => [
        'GET',                     // HTTP Method
        '/login',                  // 網址格式
        '\Controller\User:login',  // Handler
    ],
    'user'    => ['/u/{id}', '\Controller\User:profile'],
    'article' => ['/a/{id}', '\Controller\Article:display'],
];
```

## HTTP Method 

Route 的第一個項目是這個 route 能接受哪些 HTTP method。可以是個字串（只接受一個 method，例如 `GET`），或是字串陣列（接受多個 method，例如 `['GET', 'POST']`）

HTTP Method 可以省略不寫。如果省略掉，預設會用 `['GET', 'POST']`。

```php
// 我們兩個是一樣的
'login' => ['/login', '\Controller\User:login']
'login' => [['GET', 'POST'], '/login', '\Controller\User:login']
```

## 網址格式

第二個元素是網址格式。網址格式遵照  [fast-route's syntax](https://github.com/nikic/FastRoute#defining-routes) 的語法：

- `/user` 只對到 /user
- `/user/{id:\d+}` 會對到 /user/42 與 /user/53 ，但不會對到 /user 跟 /user/xyz
- `/user/{name}` 會對到 /user/foobar ，不會對到 /user/foo/bar
- `/user/{name:.+}` 也會對到 /user/foo/bar
- `/user/{id:\d+}[/{name}]` 會對到 /user/42 以及 /user/42/xyz
- `/user[/{id:\d+}[/{name}]]` 可以在非必需項目裡面再包一個非必需項目

網址格式也可以省略。如果省略掉，預設會用 route 的名字。

```php
// 這三個是一樣的
'login' => ['\Controller\User:login']
'login' => ['/login', '\Controller\User:login']
'login' => [['GET', 'POST'], '/login', '\Controller\User:login']
```

## Request Handler

第三個項目是 request handler。你可以只設定一個 handler，也可以設定多個 handler 組成的陣列。

詳細請參照 [Request handler](request_handler.md) 文件。

你**不可以**省略 Request Handler（如果你不設，那你一開始幹嘛要有這個 route？）

### 預設 route

MinorWork 預設了這些 route。

- `default` 會在沒有對到其他 route 的時候被拿來用，這等於是 404 handler。
- `defaultError` 會在程式拋出 exception 的時候被呼叫，這等於是 500 handler。

如果你想蓋掉預設的 route handler，定義同名的 route 就可以了。

