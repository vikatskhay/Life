<?php
/**
 * Created by JetBrains PhpStorm.
 * User: Vika
 * Date: 12.06.12
 * Time: 10:23
 */

class Logger
{
    private static $_logPath;

    public static function init($config)
    {
        assert(array_key_exists('path', $config));

        self::$_logPath = APPLICATION_PATH . $config['path'];
    }
    

    public static function __callStatic($logLevel, $args)
    {
        assert(isset($args[0]));

        $logLevel = strtoupper($logLevel);

        $message = $args[0];
        $message = date('d.m.Y H:i:s') . " <$logLevel> $message";

        self::_log($message);
    }

    private static function _log($message)
    {
        $filename = self::$_logPath . date('Y-m-d') . '.log';

        $hd = fopen($filename, 'a');
        flock($hd, LOCK_EX);
        fwrite($hd, $message . "\n");
        fclose($hd);
    }

}
