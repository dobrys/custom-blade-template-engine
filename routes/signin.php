<?php

use App\SessionManager;
use App\Config;

// ако НЕ е логнат → обратно към login
if (!SessionManager::isLoggedIn()) {
    header('Location: /login');
    exit;
}
$next = Config::get('next_uri_var');

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