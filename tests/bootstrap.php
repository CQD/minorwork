<?php
namespace MinorWork;

include __DIR__ . '/../vendor/autoload.php';

class HeaderMock
{
    private static $headers = [];
    public static function add($header)
    {
        $key = strtoupper(strtok(':', $header));
        static::$headers[$key] = $header;
    }
    public static function remove($name = null)
    {
        if (null === $name) {
            static::$headers = [];
        } else {
            $key = strtoupper(strtok(':', $header));
            unset(static::$headers[$key]);
        }
    }
    public static function listAll()
    {
        return array_values(static::$headers);
    }
}

function header($header)
{
    HeaderMock::add($header);
}

function header_remove($name = null)
{
     HeaderMock::remove($name);
}
