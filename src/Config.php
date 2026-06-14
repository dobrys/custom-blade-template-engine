<?php

namespace App;

/**
 * Статичен facade над config.php масива + изчислени пътища спрямо app root +
 * кеширано зареждане на config/nav.php (за да не се чете 2 пъти на заявка).
 */
final class Config
{
    private static array $items = [];
    private static array $cache = [];

    public static function load(array $config): void
    {
        self::$items = $config;
    }

    public static function get(string $key, mixed $default = null): mixed
    {
        return self::$items[$key] ?? $default;
    }

    public static function basePath(string $path = ''): string
    {
        $base = rtrim(dirname(__DIR__), '/\\');

        return $path === '' ? $base : $base . '/' . ltrim($path, '/\\');
    }

    public static function configPath(string $file = ''): string
    {
        return self::basePath('config/' . $file);
    }

    public static function themesPath(string $theme): string
    {
        return self::basePath('themes/' . $theme);
    }

    public static function routesPath(string $file = ''): string
    {
        return self::basePath('routes/' . $file);
    }

    public static function nav(): array
    {
        return self::$cache['nav'] ??= require self::configPath('nav.php');
    }
}
