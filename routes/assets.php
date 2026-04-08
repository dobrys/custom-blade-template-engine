<?php
$cfg   = require __DIR__ . '/../config.php';
$theme = $cfg['theme'] ?? 'default';

$baseDir = realpath(__DIR__ . '/../themes/' . $theme);

$uri     = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

// махаме /themes/{theme}/
$path = preg_replace('#^/themes/[^/]+/#', '', $uri);

$fullPath = realpath($baseDir . '/' . $path);

if (!$fullPath || strpos($fullPath, $baseDir) !== 0) {
    http_response_code(403);
    exit('Forbidden');
}

if (!file_exists($fullPath) || is_dir($fullPath)) {
    http_response_code(404);
    exit('Not Found');
}

// --- MIME по разширение (надежден за текстови формати) ---
$ext = strtolower(pathinfo($fullPath, PATHINFO_EXTENSION));

$mimeMap = [
    // Web
    'css'   => 'text/css',
    'js'    => 'application/javascript',
    'mjs'   => 'application/javascript',
    'html'  => 'text/html; charset=utf-8',
    'htm'   => 'text/html; charset=utf-8',
    'json'  => 'application/json',
    'xml'   => 'text/xml',          // по-съвместимо от application/xml
    'cur'   => 'image/x-win-bitmap', // cursor файлове (рядко)
    'gz'    => 'application/gzip',  // ако сервирате compressed assets
    'svg'   => 'image/svg+xml',
    'webmanifest' => 'application/manifest+json',
    // Изображения
    'png'   => 'image/png',
    'jpg'   => 'image/jpeg',
    'jpeg'  => 'image/jpeg',
    'gif'   => 'image/gif',
    'webp'  => 'image/webp',
    'ico'   => 'image/x-icon',
    'avif'  => 'image/avif',
    // Шрифтове
    'woff'  => 'font/woff',
    'woff2' => 'font/woff2',
    'ttf'   => 'font/ttf',
    'otf'   => 'font/otf',
    'eot'   => 'application/vnd.ms-fontobject',
    // Медия
    'mp4'   => 'video/mp4',
    'webm'  => 'video/webm',
    'mp3'   => 'audio/mpeg',
    'ogg'   => 'audio/ogg',
    // Архиви / misc
    'pdf'   => 'application/pdf',
    'txt'   => 'text/plain; charset=utf-8',
    'map'   => 'application/json',   // source maps
];

if (isset($mimeMap[$ext])) {
    $mime = $mimeMap[$ext];
} else {
// Fallback към finfo за непознати бинарни файлове
$finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime  = finfo_file($finfo, $fullPath);
    finfo_close($finfo);
}

header('Content-Type: ' . $mime);
header('Cache-Control: public, max-age=31536000');
header('Content-Length: ' . filesize($fullPath));

readfile($fullPath);
exit;