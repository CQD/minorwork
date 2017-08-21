# MinorWork Framework 總覽

這裡是個迷你的 MinorWork 使用範例：

```php
$app = new \MinorWork\App; // 建立容器

$app->setRouting($route);  // 設定 routing

$app->run();               // 找出對應的 route
                           // 執行 request handler
                           // 輸出結果
```

你可以用 composer 安裝 MinorWork，支援 PHP 5.4 以上版本。

```
composer require minorwork/minorwork
```

如果要在實際案例中使用 MinorWork，你需要理解下面幾個概念：

- [容器](#container)
- [Routing](#routing)
- [Request handler](#request_handler)
- [輸出畫面](#rendering_output)

若你用過其他的 framework（例如 Laravel 或 Slim），你可能已經知道容器跟 routing 在幹嘛。但 request handler 跟其他 framework 稍微有點不一樣。

MinorWork 裡面沒有 service provider 或 middleware 的概念。

<a name='container'></a>
## 容器

容器的意思是「你要把所有東西都放在這裡面」。

`\MinorWork\App` class 是 MinorWork 的核心，也是 [DI](https://www.google.com/search?q=dependency+injection) 容器。

用起來像是這樣：

```php
$app = new App;
$app->set('dataLoader', '\Data\Loader\Class');  // 設定 Class Name
$dataLoader = $app->get('dataLoader');          // 從容器裡面取值
```

詳細請參照[容器](container.md)文件。

<a name='routing'></a>
## Routing

Routing 的意思是「依照網址決定要跑什麼邏輯」


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

一個 route 裡面包含這些項目

- 名稱 (route 在陣列裡面的 key)
- HTTP Method (預設：`['GET', 'POST']`)
- 網址格式 (預設：route 的名字)
- Request Handler

MinorWork 預設了這些 route。

- `default` 會在沒有對到其他 route 的時候被拿來用，這等於是 404 handler。
- `defaultError` 會在程式拋出 exception 的時候被呼叫，這等於是 500 handler。

如果你想蓋掉預設的 route handler，定義同名的 route 就可以了。

詳細請參照 [routing](routing.md) 文件。

<a name='request_handler'></a>
## Request handler

Request Handler 是邏輯本體所在的地方，也是在這裡設定使用者應該看到什麼。

設定 Request Handler 的方式有下列幾種：

- controller/method 字串（建議作法）
  - 例如 `'\Controller\User:login'`
- PHP [callable](http://php.net/manual/en/language.types.callable.php)。例如函式名稱、或 closure、或其他的 callable 類別。

被呼叫到的時候， request handler 會收到三個參數：

- 容器物件
- router 對到的網址參數
  - 網址格式如果是 `/user/{id:\d+}[/{name}]`，對於 `/user/42` 來說 `$params` 會是 `['id' => 42]`
- 前一個 handler 得回傳值，如果沒有「前一個 handler」，那會是 null。

簡單的 handler 大概會長這樣：

```php
function basicHandler($app, $params, $prev) {
    $app->view->prepare([
        'params' => $params,
        '$prev' => $prev,
    ]);
}
```

詳細請參照 [request handler](request_handler.md) 文件。

<a name='rendering_output'></a>
## Rendering Output

要把資料輸出給使用者，你可以把要輸出的字串直接設定給 `$app->view`，也可以自訂 view handler（只要是能夠轉型成字串的東西都可以）。

你不應該在 request handler 裡面 echo 東西。MinorWork 會在所有的 request handler 都跑完之後才輸出畫面，所以你隨時可以改變想要輸出的資料。

MinorWork 內建了兩款簡單的樣版引擎：

- `\MinorWork\View\SimpleView` 是個以 `str_replace` 為基礎的樣版引擎，預設會用這個。
- `\MinorWork\View\JsonView` 是個以 `json_encode` 為基礎的 JSON 輸出引擎。

詳細請參照 [view](view.md) 文件。

<a name='helper'></a>
## 工具函式

MinorWork 也內建了一些工具函式

`$app->routePath($routeName, $params = [], $query = [])` 用來回傳某個 route 的實際網址。`$param` 用來填滿網址裡面的變數。如果有給 `$query`，網址後面會接上 query string。如果無法產生網址，會拋出 Exception。

`$app->routeFullPath($routeName, $params = [], $query = [])` 做的事情跟 `routePath()` 幾乎一樣，但是回傳的是包含 schema 跟主機名稱的完整網址（例：`https://example.com/api/users/123?format=json`）

`$app->redirectTo($routeName, $params = [], $query = [])` 會把使用者轉址到 `routePath()` 指定的路徑，然後停止程式執行。


