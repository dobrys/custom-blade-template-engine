<?php

namespace App\Auth;

interface LoginInterface {
    /**
     * Опит за логин
     *
     * @param array $credentials Може да съдържа:
     *   - ['username'=>'...', 'password'=>'...']
     *   - ['phone'=>'...']
     *
     * @return bool Успешен ли е логина
     */
    public function attempt(array $credentials): bool;
}
