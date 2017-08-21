# 容器

`\MinorWork\App` class 是 MinorWork 的核心，也是 [DI](https://www.google.com/search?q=dependency+injection) 容器。你可以把東西註冊進容器，然後在你的程式的任何地方用你原本註冊的名字取用你事先設定好的東西。容器裡面的物件是 singleton 的，物件只會被建立一次，不論你何時呼叫，你拿到的都是同一個物件。

註冊方法有三種：

- 設定 class name
  - 取用時，會拿到該 class 的物件
- 設定 function
  - 這個 Function 會被當成產生物件的工廠函式。取用項目時 Function 會被呼叫一次，然後回傳 function 的 return 值。
- 物件、scalar、陣列
  - 資料會被存下來，每次取用就直接回傳給你。

用起來像是這樣

```php
$app = new App;

// 設定 Class Name
$app->set('dataLoader', '\Data\Loader\Class');  // 物件的 class name
$app->dataLoader = '\Data\Loader\Class';        // 你也可以這樣設

// 設定工廠函式
$app->dataLoader = function() use ($params) {
    return new \DataLoader($params);
};

// 設定預先準備好的實體
$app->dataLoader = new DataLoader();
$app->thatNumber = 42;

// 從容器裡面取值
$dataLoader = $app->get('dataLoader');          // 取得 Data loader 物件
$dataLoader = $app->dataLoader;                 // 也可以這樣取
echo $app->thatNumber;                          // 會印出 `42`
```

MinorWork 的容器很 [lazy](https://google.com/search?q=lazy+initialization)，他只有在物件第一次被取用的時候才建立物件。所以你的程式只有在用到的時候才會被載入，減少不必要的效能/記憶體開銷。

當你想要到處重用邏輯物件，卻不想到處重新 new 物件的時候，容器會對你很有幫助。

容器在寫 Unit test 的時候也很好用。你可以在測試裡面取代掉原本註冊好的物件，而不用去動到實際的邏輯，也不需要引入又慢又肥的 mocking 機制。

MinorWork 沒有獨立的 Service Provider 機制，所有需要的邏輯都往同一個容器裡面註冊。

## 預設項目

MinorWork 會預先設定好這些項目：

- `_GET`, `_POST`, `_SERVER` 對應到同名的 super global 
- `view` 被設定成 `\MinorWork\View\SimpleView`，一個基於 `str_replace` 的簡單樣版引擎。
- `session` 被設定成 `\MinorWork\Session\NativeSession`，這是個 PHP 原生 Session 的 wrapper，同時支援 flash message。

