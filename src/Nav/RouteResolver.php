<?php

namespace App\Nav;

use App\Config;
use App\SessionManager;

/**
 * Превръща request URI в route slug и решава дали да го запомни като
 * "next_page" за пост-login redirect.
 */
class RouteResolver
{
    public function resolve(string $uri): string
    {
        $route = trim($uri, '/');

        if ($route === '') {
            $route = 'home';
        } elseif (str_starts_with($route, 'themes')) {
            $route = 'assets';
        } elseif (!preg_match('/^[a-zA-Z0-9\/_\-]+(\.php)?$/', $route)) {
            http_response_code(400);
            echo "Bad Request";
            exit;
        } else {
            $route = preg_replace('/\.php$/', '', $route);
        }

        $this->trackNextPage($uri, $route);

        return $route;
    }

    private function trackNextPage(string $uri, string $route): void
    {
        if ($route === 'assets') {
            return;
        }

        $special = Config::get('special_uri');
        if (!in_array($uri, $special, true)) {
            SessionManager::set(Config::get('next_uri_var'), $uri);
        }
    }
}
