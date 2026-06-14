<?php
if (!function_exists('asset')) {
    function asset(string $path): string
    {
        $base = 'https://' . $_SERVER['HTTP_HOST'];
        //return rtrim($_SERVER['REQUEST_SCHEME'] . '://' . $_SERVER['HTTP_HOST'], '/') . '/' . ltrim($path, '/');
        return rtrim($base, '/') . "/assets/" . ltrim($path, '/');
    }
}
if (!function_exists('theme_asset')) {
    function theme_asset(string $path): string
    {
        // Глобалната тема идва от BladeEngine конструктора
        $theme = $GLOBALS['current_theme'] ?? 'default';

        // Винаги използваме https
        $base = 'https://' . $_SERVER['HTTP_HOST'];

        return rtrim($base, '/') . "/themes/{$theme}/assets/" . ltrim($path, '/');
    }
}

/**
 * Взема параметър от POST, GET или и двата, с default стойност и опция за източник.
 *
 * @param string $key Името на параметъра
 * @param mixed $default Стойност по подразбиране, ако параметърът не съществува
 * @param string $source От къде да търсим: 'post', 'get', 'request' (default: 'request')
 * @param bool $sanitize Да се почисти ли стойността от потенциално опасни символи (default: true)
 * @return mixed Върната стойност на параметъра, или $default ако липсва
 */
function input(string $key, $default = null, string $source = 'request', bool $sanitize = true) {
    switch (strtolower($source)) {
        case 'post':
            $val = $_POST[$key] ?? null;
            break;
        case 'get':
            $val = $_GET[$key] ?? null;
            break;
        case 'request':
        default:
            $val = $_REQUEST[$key] ?? null;
            break;
    }

    if ($val === null) {
        return $default;
    }

    if ($sanitize) {
        if (is_array($val)) {
            // Рекурсивна филтрация на масиви
            array_walk_recursive($val, function (&$item) {
                $item = htmlspecialchars(trim($item), ENT_QUOTES, 'UTF-8');
            });
            return $val;
        } else {
            return htmlspecialchars(trim($val), ENT_QUOTES, 'UTF-8');
        }
    } else {
        return $val;
    }
}

if (!function_exists('env')) {
    function env(string $key, mixed $default = null): mixed
    {
        if (isset($_ENV[$key])) return $_ENV[$key];
        if (isset($_SERVER[$key])) return $_SERVER[$key];

        $val = getenv($key);
        return $val !== false ? $val : $default;
    }
}

if (!function_exists('site_var')) {
    /**
     * Locale-specific конфигурационна стойност от config/site_vars.php
     * (телефон/имейл/цена/правен текст и др. по език).
     *
     * Ключ за избор на език е $_SESSION['app_language'] (зададен от
     * LanguageDetector::persist()), с fallback към 'default'.
     */
    function site_var(string $key): string
    {
        static $vars = null;
        if ($vars === null) {
            $vars = require \App\Config::configPath('site_vars.php');
        }

        $lang = \App\SessionManager::get('app_language') ?? 'default';

        return (string)($vars[$lang][$key] ?? $vars['default'][$key] ?? '');
    }
}

