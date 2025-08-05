<?php

namespace App;
use PDO;

class DatabaseFactory
{
    private static $connections = [];

    public static function getConnection($configPath)
    {
        if (!isset(self::$connections[$configPath])) {
            $config = require $configPath;

            $dsn = "mysql:host={$config['host']};dbname={$config['database']};charset={$config['charset']}";
            self::$connections[$configPath] = new PDO($dsn, $config['username'], $config['password'], [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
            ]);
        }
        return self::$connections[$configPath];
    }
}