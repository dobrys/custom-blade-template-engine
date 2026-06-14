<?php

use App\Auth\AuthJwt;
use App\SessionManager;

if (($_ENV['JWT_REMOVE_ON_LOGOUT'] ?? 'false') === 'true') {
    $jwt = new AuthJwt(env('JWT_SECRET'));
    $jwt->removeJWT();
}

SessionManager::start();
SessionManager::logout();

header('Location: /');
exit;