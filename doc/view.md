# View

To output, you can set a string into `$app->view`, or use a view handler(anything that can cast to string will do).

You should not do echo anything in request handler.
MinorWork only render view after finish execute request handler. So you can change the content any time.

MinorWork comes with two simple template engine.

- `\MinorWork\View\SimpleView` is a `str_replace` based engine. This is the default engine.
- `\MinorWork\View\JsonView` is a `json_encode` based JSON renderer.

```php
function handler($app, $params)
{
    $app->view->prepare(
        'I am a {job}, not a {not_my_job}.',
        ['job' => 'Doctor', 'not_my_job' => 'mechanic']
    );
}
```

Will output

```none
I am a Doctor, not a mechanic.
```

You can override default template engine, just override `view` in app container, anything that can cast to string (or any object that implements [__toString](http://php.net/manual/en/language.oop5.magic.php#object.tostring) magic method) will work with MinorWork. In fact you can even assign a string to `view`, and it will still get rendered.

You can also use [minorwork-twig](https://github.com/CQD/minorwork-twig) to use Twig as view renderer.


