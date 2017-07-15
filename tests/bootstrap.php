<?php
include __DIR__ . '/../vendor/autoload.php';

function getHeaders()
{
    return function_exists('xdebug_get_headers') ? xdebug_get_headers() : get_headers();
}
