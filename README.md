# MinorWork Framework

[![Build Status](https://travis-ci.org/CQD/minorwork.svg?branch=master)](https://travis-ci.org/CQD/minorwork)

A minimal PHP framework that is serious on being minimal.

Features:

- A container.
- Named routing with redirection support
- Less than 300 lines.
- Only external dependency is `nikic/fast-route` for routing.
- Optional Twig integration ([minorwork-twig](https://github.com/CQD/minorwork-twig))

## Install

You can install MinorWork with composer.

```
composer require minorwork/minorwork
```

PHP 5.4 or newer required.

## Usage

Using MinorWork framework looks like this:

```php
<?php
// This is index.php
// All traffic should bed handled by this file.
// You may need edit your .htaccess for this.

include __DIR__ . '/../vendor/autoload.php';

use MinorWork\App;

$app = new App();
$app->setRouting([
    'root'         => ['/', '\Controller\Index:home'],
    'loginForm'    => ['GET', '/login', '\Controller\Login:form'],
    'loginAction'  => ['POST', '/login', '\Controller\Login:login'],
    'article'      => ['/articles/{id}', '\Controller\Article:show'],
    'users'        => ['\Controller\User:list'], // matches /users
    'userProfile'  => ['/user/{id}', '\Controller\User:profile'],
]);

$app->run();
```

Define routing, the run the app. MinorWork will take care the rest.

Another example is in [example/](example/) folder. You can see it in action using PHP dev server.

```shell
cd example
php -S localhost:8765
```

Read the [documentation](doc/en/overview.md) for more detail.

