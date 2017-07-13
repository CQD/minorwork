<?php
include __DIR__ . '/../vendor/autoload.php';

use MinorWork\App;

$app = new App();
$app->setRouting([
    'root'       => ['/', 'itWorks'],
    'basic'      => ['itWorks'],
    'params'     => ['/p/{b1:\d+}[/{b2}]', 'itWorks'],
    'controller' => ['/c/{action}', 'ExampleController:lookingFor'],
    'redirect'   => ['/r/{name}', function($app, $params){
        $app->redirectTo($params['name'], $app->get('_GET'));
    }],
    // 'default' => [function($app, $params){echo "Override default handler!";}],
]);

$app->run();

exit;

/////////////////////////////////////////

function itWorks($app, $params){
    echo "<pre><hr>[Links]\n\n";
    echo "<a href='/basic'>/basic</a> The basic.\n";
    echo "<a href='/p/12345/ParamForB'>/p/12345/ParamForB</a> URL parameters\n";
    echo "<a href='/not_exist'>/not_exist</a> Fallback to default handler when no match found.\n";
    echo "<a href='/c/love'>/c/love</a> Controller class example\n";
    echo "<a href='/r/controller?action=peace'>/r/controller?action=peace</a> Supports named redirection\n";
    echo "\n\n<hr>[params]\n\n";
    echo json_encode($params, JSON_PRETTY_PRINT);
}

class ExampleController
{
    public function lookingFor($app, $params)
    {
        itWorks($app, $params);
        echo "<hr>You are looking for {$params['action']}\n";
    }
}
