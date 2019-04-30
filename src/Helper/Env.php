<?php

namespace MinorWork\Helper;

class Env
{
    public function __get($name)
    {
        if (defined($name)) {
            return constant($name);
        } elseif (false !== $value = getenv($name)) {
            return $value;
        } elseif (isset($_ENV[$name])) {
            return $_ENV[$name];
        }
        return null;
    }
}

