# View

要把資料輸出給使用者，你可以把要輸出的字串直接設定給 `$app->view`，也可以自訂 view handler（只要是能夠轉型成字串的東西都可以）。

你不應該在 request handler 裡面 echo 東西。MinorWork 會在所有的 request handler 都跑完之後才輸出畫面，所以你隨時可以改變想要輸出的資料。

MinorWork 內建了兩款簡單的樣版引擎：

- `\MinorWork\View\SimpleView` 是個以 `str_replace` 為基礎的樣版引擎，預設會用這個。
- `\MinorWork\View\JsonView` 是個以 `json_encode` 為基礎的 JSON 輸出引擎。

對於 SimpleView 來說

```php
function handler($app, $params)
{
    $app->view->prepare(
        'I am a {job}, not a {not_my_job}.',
        ['job' => 'Doctor', 'not_my_job' => 'mechanic']
    );
}
```

會輸出

```none
I am a Doctor, not a mechanic.
```

你可以蓋掉預設的樣版引擎，你可以把 app container 裡面的 `view` 改成任何能夠轉型成字串的東西（或是任何有實作 [__toString](http://php.net/manual/en/language.oop5.magic.php#object.tostring) 的物件）。實際上你也可以直接指定一個字串， MinorWork 也能正常輸出。

如果你想用 Twig 當作你的樣版引擎，請參考 [minorwork-twig](https://github.com/CQD/minorwork-twig)。

