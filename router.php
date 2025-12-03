<?php
global $blade;
$middlewares = [
    'auth' => \App\Middleware\AuthMiddleware::class,
];
// Основните статични маршрути
$routes = [
    'home'    => ['file' => __DIR__ . '/routes/home.php', 'middleware' => ['auth']],
    'profile' => ['file' => __DIR__ . '/routes/profile.php', 'middleware' => ['auth']],
    'login'   => ['file' => __DIR__ . '/routes/login.php'],
    'logout'   => ['file' => __DIR__ . '/routes/logout.php'],
    'single'  => ['file' => __DIR__ . '/routes/single.php'],
    'terms'   => ['file' => __DIR__ . '/routes/terms.php'],
    'subscribe'   => ['file' => __DIR__ . '/landing/mobixon/index.php'],
];

// Опитваме се да заредим routes/map.php (ако съществува)
$mapPath = __DIR__ . '/routes/map.php';
$specialRoutes = file_exists($mapPath) ? require $mapPath : [];
//die(var_dump($specialRoutes));
// Получаваме текущия път
$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$route = trim($uri, '/');

// Ако е празно, задаваме home
if ($route === '') {
    $route = 'home';
}

// Безопасност
if (!preg_match('/^[a-zA-Z0-9\/_\-]+(\.php)?$/', $route)) {
    http_response_code(400);
    echo "Bad Request";
    exit;
}

$route = preg_replace('/\.php$/', '', $route);

// 🔥 Проверяваме дали има специално съвпадение
foreach ($specialRoutes as $pattern => [$file, $params, $mws]) {
    if (preg_match($pattern, $route, $matches)) {

        foreach ($params as $index => $paramName) {
            $_GET[$paramName] = $matches[$index + 1] ?? null;
        }
        $_REQUEST = array_merge($_REQUEST, $_GET);

        // Проверка дали има middleware и е масив
        if (!empty($mws) && is_array($mws)) {
            foreach ($mws as $mwName) {
                if (isset($middlewares[$mwName])) {
                    (new $middlewares[$mwName])->handle();
                }
            }
        }

        $target = __DIR__ . '/routes/' . ltrim($file, '/');
        //$target = __DIR__ . '/' . ltrim($file, '/');
        if (file_exists($target)) {

            require_once $target;
            exit;
        } else {
            //dump($target);
            http_response_code(500);
            echo "Routing error: File not found for $file !!!";
            exit;
        }
    }
}

/*foreach ($specialRoutes as $pattern => [$file, $params]) {
    if (preg_match($pattern, $route, $matches)) {
        // Записваме параметрите в $_GET
        foreach ($params as $index => $paramName) {
            $_GET[$paramName] = $matches[$index + 1] ?? null;
        }

        // Обновяваме $_REQUEST
        $_REQUEST = array_merge($_REQUEST, $_GET);

        $target = __DIR__ . '/routes/' . ltrim($file, '/');

        if (file_exists($target)) {
            require_once $target;
            exit;
        } else {
            http_response_code(500);
            echo "Routing error: File not found for $file";
            exit;
        }
    }
}*/


// Ако е дефиниран статичен маршрут
if (isset($routes[$route])) {

    $info = $routes[$route];

    // Проверка дали има middleware и е масив
    if (!empty($info['middleware']) && is_array($info['middleware'])) {
        foreach ($info['middleware'] as $mwName) {
            if (isset($middlewares[$mwName])) {
                //dump($route,$info);
                (new $middlewares[$mwName])->handle();
            }
        }
    }
    //var_dump($route,$info['file']);
    $blade->assign('siteURL', $route . '/');
    $blade->assign('user', $route . '/account/');
    require_once $info['file'];
    exit;
}

// Ако няма съвпадение
http_response_code(404);
$blade->assign('missingRoute', $route);
echo $blade->render('errors.404', ['title' => __('Page Not Found')]);
