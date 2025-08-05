<?php

namespace App;

class SessionManager
{
    public static function start(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
    }

    public static function destroy(): void
    {
        if (self::isStarted()) {
            $_SESSION = [];
            session_destroy();
        }
    }

    public static function isStarted(): bool
    {
        return session_status() === PHP_SESSION_ACTIVE;
    }

    public static function isLoggedIn(): bool
    {
        return self::isStarted() && isset($_SESSION['user_id']);
    }

    public static function get(string $key)
    {
        return $_SESSION[$key] ?? null;
    }

    public static function set(string $key, $value): void
    {
        self::start();
        $_SESSION[$key] = $value;
    }
    public static function getUserId(){
        if (self::isStarted() && isset($_SESSION['user_id'])) {
            return $_SESSION['user_id'];
        }
    }

    public static function clear(string $key): void
    {
        unset($_SESSION[$key]);
    }
}