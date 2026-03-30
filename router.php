<?php
require_once __DIR__ . '/init.php';
global $blade;

$middlewares = [
    'auth' => \App\Middleware\AuthMiddleware::class,
];

$routes = [
    'assets'    => ['file' => __DIR__ . '/routes/assets.php'],
    'home'      => ['file' => __DIR__ . '/routes/home.php',            ],
    'profile'   => ['file' => __DIR__ . '/routes/profile.php',          'middleware' => ['auth']],
    'login'     => ['file' => __DIR__ . '/routes/login.php'],
    'logout'    => ['file' => __DIR__ . '/routes/logout.php'],
    'single'    => ['file' => __DIR__ . '/routes/single.php'],
    'terms'     => ['file' => __DIR__ . '/routes/terms.php'],
    'subscribe' => ['file' => __DIR__ . '/landing/mobixon/index.php'],
];


(new \App\Nav\Router($routes, $middlewares, $blade))->dispatch();