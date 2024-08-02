<?php

namespace App\Behat\Context;

class SharedContext
{
    private static $data = [];

    public static function set($key, $value)
    {
        self::$data[$key] = $value;
    }

    public static function get($key)
    {
        return self::$data[$key] ?? null;
    }

    public static function reset()
    {
        return self::$data = [];
    }
}
