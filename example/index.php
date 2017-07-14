<?php
include __DIR__ . '/../vendor/autoload.php';

use MinorWork\App;

$app = new App();
$app->setRouting([
    'root'       => ['/', 'itWorks'],
    'basic'      => ['itWorks'],
    'params'     => ['/p/{b1:\d+}[/{b2}]', 'itWorks'],
    'controller' => ['/c/{action}', 'ExampleController:lookingFor'],
    'simpleview' => ['simpleView'],
    'redirect'   => ['/r/{name}', function($app, $params){
        $app->redirectTo($params['name'], $app->get('_GET'));
    }],
    // 'default' => [function($app, $params){echo "Override default handler!";}],
]);

$app->run();

exit;

/////////////////////////////////////////

function simpleView($app, $params)
{
    $app->view->prepare(
        'I am a {job}, not a {not_my_job}.',
        ['job' => 'Doctor', 'not_my_job' => 'mechanic']
    );
}

function itWorks($app, $params)
{
    $template = <<<TEMPLATE
<pre><hr>[Links]

<a href='/basic'>/basic</a> The basic.
<a href='/p/12345/ParamForB'>/p/12345/ParamForB</a> URL parameters.
<a href='/not_exist'>/not_exist</a> Fallback to default handler when no match found.
<a href='/c/love'>/c/love</a> Controller class example.
<a href='/simpleview'>/simpleview</a> SimpleView
<a href='/r/controller?action=peace'>/r/controller?action=peace</a> Supports named redirection.

<hr>[params]

TEMPLATE;
    $template .= json_encode($params, JSON_PRETTY_PRINT);
    $app->view->prepare($template);
}

class ExampleController
{
    public function lookingFor($app, $params)
    {
        itWorks($app, $params);
        $view = $app->view;
        $view->prepare("{$view}<hr>You are looking for {$params['action']}");
    }
}
