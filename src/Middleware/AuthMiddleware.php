<?php

namespace App\Middleware;

use App\Auth\AuthService;
use App\Auth\AuthJwt;
use App\Auth\Providers\NthProvider;
use App\Auth\Providers\DummyProvider;
use App\Config;

class AuthMiddleware
{
    public function handle(): void
    {
        $providers = Config::get('env') === 'development'
            ? [new DummyProvider()]
            : [new NthProvider()];

        $auth = new AuthService(new AuthJwt(env('JWT_SECRET')), $providers, '/login');
        $auth->handle();
    }
}