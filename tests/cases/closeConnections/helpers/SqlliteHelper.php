<?php

declare(strict_types=1);

namespace tests\helpers;

class SqlliteHelper
{
    protected static $temp_name;

    public static function getTmpFile()
    {
        if (empty(self::$temp_name)) {
            self::$temp_name = tempnam('', '/file0');
        }
        return self::$temp_name;
    }

    public static function connectionCount()
    {
        $path = self::$temp_name;
        $count = shell_exec("lsof -w {$path} | grep {$path} | wc -l");
        return (int)$count;
    }

    public static function debug()
    {
        $path = self::$temp_name;
        $cmd = "lsof -w {$path}";
        codecept_debug("Executing : $cmd");
        codecept_debug(shell_exec($cmd));
    }

}