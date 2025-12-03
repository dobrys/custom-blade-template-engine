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

            // ????????? ?? ???????? cookie, ??? ??????????
            if (ini_get("session.use_cookies")) {
                $params = session_get_cookie_params();
                setcookie(
                    session_name(),
                    '',
                    time() - 42000,
                    $params["path"],
                    $params["domain"],
                    $params["secure"],
                    $params["httponly"]
                );
            }

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

    public static function getUserId()
    {
        if (self::isStarted() && isset($_SESSION['user_id'])) {
            return $_SESSION['user_id'];
        }
        return null;
    }

    public static function clear(string $key): void
    {
        unset($_SESSION[$key]);
    }

    public static function logout(): void
    {
        if (self::isLoggedIn()) {
            self::destroy();
        }
    }
}
