<?php

function generateRoutesMap(string $baseDir, string $prefix = ''): array
{
    $routes = [];

    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($baseDir),
        RecursiveIteratorIterator::LEAVES_ONLY
    );

    foreach ($iterator as $file) {
        if ($file->isFile() && $file->getExtension() === 'php') {
            $filePath = $file->getPathname();

            // Преобразуваме в относителен път към $baseDir
            $relativePath = str_replace('\\', '/', substr($filePath, strlen($baseDir) + 1));

            // Премахваме разширението .php
            $routeKey = substr($relativePath, 0, -4); // без ".php"

            // Опционално добавяме префикс (например 'admin/')
            $fullRouteKey = ltrim($prefix . $routeKey, '/');

            // Унифицираме пътя към скрипта с '/'
            $normalizedPath = str_replace('\\', '/', $filePath);

            // Създаваме относителен път от текущата директория (__DIR__)
            $relativeScriptPath = '__DIR__ . \'/routes/' . str_replace("'", "\\'", $relativePath) . '\'';

            $routes[$fullRouteKey] = $relativeScriptPath;
        }
    }

    ksort($routes); // Подреждаме по ключове

    return $routes;
}

// 📌 Използване:
$baseDir = __DIR__ . '/../routes';
$routes = generateRoutesMap($baseDir);

// 📦 Генериране на PHP код с относителни пътища:
$output = "<?php\n\n\$routes = [\n";

foreach ($routes as $key => $value) {
    $output .= "    '" . $key . "' => {$value},\n";
}

$output .= "];\n";

// Записване във файл или директно извеждане:
file_put_contents(__DIR__ . '/routes_map.php', $output);
echo "✅ Файлът routes_map.php е създаден успешно.\n";
