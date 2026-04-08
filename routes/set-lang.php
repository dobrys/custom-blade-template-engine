<?php
global $detector;

$locale = $_GET['locale'] ?? '';
$back   = $_GET['back']   ?? '/';

// Валидация — само познати локали
if ($detector->isValidLocale($locale)) {
    $detector->setLocale($locale);
    $detector->persist();
}

// Sanitize redirect — само relative paths
$back = '/' . ltrim(parse_url($back, PHP_URL_PATH), '/');

header('Location: ' . $back, true, 302);
exit;