<?php

namespace App\Middleware;

use App\Auth\AuthService;
use App\Auth\Providers\NthProvider;
use App\Auth\AuthJwt;

class AuthMiddleware
{
    public function handle(): void
    {
        $jwt = new AuthJwt(SK);

        $providers = [
            new NthProvider(),
        ];

        $auth = new AuthService($jwt, $providers, '/login');
        $auth->handle();
    }
}