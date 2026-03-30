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

if (!file_exists($fullPath)) {
    http_response_code(404);
    exit('Not Found');
}

// MIME
$finfo = finfo_open(FILEINFO_MIME_TYPE);
header('Content-Type: ' . finfo_file($finfo, $fullPath));
header('Cache-Control: public, max-age=31536000');

readfile($fullPath);
exit;