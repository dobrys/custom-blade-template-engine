<?php

namespace App\Auth;

use App\Auth\LoginInterface;

class DefaultLoginHandler implements LoginInterface
{

    /**
     * @inheritDoc
     */
    public function attempt(array $credentials): bool
    {
        \App\SessionManager::start();
        \App\SessionManager::set('user_id', 42);
        //dump($credentials);
        return true;
    }
}