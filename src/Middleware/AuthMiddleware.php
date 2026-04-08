<?php

namespace App\Middleware;

use App\Auth\AuthService;
use App\Auth\AuthJwt;
use App\Auth\Providers\NthProvider;
use App\Auth\Providers\DummyProvider;

class AuthMiddleware
{
    public function handle(): void
    {
        //die(var_dump(SK));
        global $config;

        $providers = $config['env'] === 'development'
            ? [new DummyProvider()]
            : [new NthProvider()];

        $auth = new AuthService(new AuthJwt(env('JWT_SECRET')), $providers, '/login');
        $auth->handle();
    }
}