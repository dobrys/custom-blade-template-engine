<?php

use App\SessionManager;

// ако НЕ е логнат → обратно към login
if (!SessionManager::isLoggedIn()) {
    header('Location: /login');
    exit;
}
global $config;
$special = $config['special_uri'];
$next = $config['next_uri_var'];

// взимаме запазения URL
$redirect = SessionManager::get($next) ?? '/';

// чистим го
SessionManager::clear($next);

// защита
if (!str_starts_with($redirect, '/')) {
    $redirect = '/';
}

// redirect
header("Location: $redirect");
exit;