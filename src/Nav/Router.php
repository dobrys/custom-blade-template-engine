<?php

namespace App\Nav;

use App\Nav\NavBuilder;
use App\SessionManager;

class Router
{
    private array  $routes;
    private array  $middlewares;
    private array  $specialRoutes;
    private string $route;
    private $blade;

    public function __construct(array $routes, array $middlewares, $blade)
    {
        $this->routes        = $routes;
        $this->middlewares   = $middlewares;
        $this->blade         = $blade;
        $this->specialRoutes = $this->loadSpecialRoutes();
        $this->route         = $this->resolveRoute();
    }

    public function dispatch(): void
    {
        if ($this->matchSpecialRoutes()) {
            return;
        }

        if ($this->matchStaticRoute()) {
            return;
        }

        $this->notFound();
    }

    private function resolveRoute(): string
    {
        $uri   = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
        $route = trim($uri, '/');

        if ($route === '') {
            return 'home';
        }

        if (!preg_match('/^[a-zA-Z0-9\/_\-]+(\.php)?$/', $route)) {
            http_response_code(400);
            echo "Bad Request";
            exit;
        }

        return preg_replace('/\.php$/', '', $route);
    }

    private function loadSpecialRoutes(): array
    {
        $mapPath = __DIR__ . '/../routes/map.php';
        return file_exists($mapPath) ? require $mapPath : [];
    }

    private function matchSpecialRoutes(): bool
    {
        foreach ($this->specialRoutes as $pattern => [$file, $params, $mws]) {
            if (preg_match($pattern, $this->route, $matches)) {
                foreach ($params as $index => $paramName) {
                    $_GET[$paramName] = $matches[$index + 1] ?? null;
                }
                $_REQUEST = array_merge($_REQUEST, $_GET);

                $this->runMiddlewares($mws);
                $this->refreshNavAndAuth();

                $target = __DIR__ . '/../routes/' . ltrim($file, '/');
                if (!file_exists($target)) {
                    http_response_code(500);
                    echo "Routing error: File not found for $file";
                    exit;
                }

                require_once $target;
                exit;
            }
        }
        return false;
    }

    private function matchStaticRoute(): bool
    {
        if (!isset($this->routes[$this->route])) {
            return false;
        }

        $info = $this->routes[$this->route];

        $this->runMiddlewares($info['middleware'] ?? []);
        $this->refreshNavAndAuth();

        if (!file_exists($info['file'])) {
            $this->notFound();
        }

        $this->blade->assign('siteURL', $this->route . '/');
        $this->blade->assign('user', $this->route . '/account/');
        require_once $info['file'];
        exit;
    }

    private function runMiddlewares(array $mws): void
    {
        foreach ($mws as $mwName) {
            if (isset($this->middlewares[$mwName])) {
                (new $this->middlewares[$mwName])->handle();
            }
        }
    }

    private function refreshNavAndAuth(): void
    {
        $isLoggedIn = SessionManager::isLoggedIn();

        $this->blade->assign('is_logged_in', $isLoggedIn);

        $nav = new NavBuilder(
            require __DIR__ . '/../../config/nav.php',
            $isLoggedIn,
            $this->route
        );

        $this->blade->assign('nav',        $nav->build());
        $this->blade->assign('currentUrl', $nav->getCurrentUrl());
    }

    private function notFound(): void
    {
        http_response_code(404);
        $this->blade->assign('missingRoute', $this->route);
        echo $this->blade->render('errors.404', ['title' => __('Page Not Found')]);
        exit;
    }
}