<?php
global $blade;

require_once __DIR__ . '/init.php';
require_once __DIR__ . '/globals.php';
//статичен мапинг
//ако се добавят страници - да се добавят и в масива !
// виж  utils/generate_routes_map.php
$routes = [
    'home' => __DIR__ . '/routes/home.php',
];


// Вземаме URI пътя
$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

$route = trim($uri, '/');

// Ако е празно (напр. '/'), го правим 'home'
if ($route === '') {
    $route = 'home';
}

// ⚠️ Защита: позволяваме само букви, цифри, наклонени черти, тирета и долна черта
if (!preg_match('/^[a-zA-Z0-9\/_\-]+(\.php)?$/', $route)) {
    http_response_code(400);
    echo "Bad Request";
    exit;
}

// Премахваме разширението, ако има
$route = preg_replace('/\.php$/', '', $route);

// Крайният път към файла
$routeFile = __DIR__ . "/routes/{$route}.php";

// проверяваме дали го има в map-а
if (isset($routes[$route])) {
    require_once $routes[$route];
    exit;
}

// Зареждаме ако съществува
/*if (file_exists($routeFile)) {
    require_once $routeFile;
    exit;
}*/

// 404 страница, ако не е намерен
http_response_code(404);
$blade->assign('missingRoute', $route);
echo $blade->render('errors.404', ['title' => __('Page Not Found')]);
