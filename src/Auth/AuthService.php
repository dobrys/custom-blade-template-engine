<?php

namespace App\Auth;

use App\authJwt;
use App\SessionManager;

class AuthService
{
    private authJwt $jwt;
    /** @var AuthProviderInterface[] */
    private array $providers;
    private string $logoutPage;

    public function __construct(authJwt $jwt, array $providers = [], string $logoutPage = '/login')
    {
        $this->jwt = $jwt;
        $this->providers = $providers;
        $this->logoutPage = $logoutPage;

        SessionManager::start();
    }

    /**
     * Основна логика: проверява състояние и прави redirect при нужда.
     */
    public function handle(): void
    {
        // Вече логнат и токенът е валиден
        if ($this->isAuthenticated()) {
            return;
        }

        // Ако има UUID (примерно от външен provider)
        if (!empty($_REQUEST['public_uuid'])) {
            $this->loginWithUuid($_REQUEST['public_uuid']);
            return;
        }
        if (!empty($_REQUEST['msisdn'])) {
            $this->loginWithMsisdn($_REQUEST['msisdn']);
            return;
        }

        // Ако токенът е изтекъл
        if ($this->jwt->haveJwt() && $this->jwt->isExpired()) {
            $this->logout('Session expired. Please log in again.');
        }

        // Няма сесия или токен → redirect
        $this->redirectToLogin();
    }

    /**
     * Проверка дали потребителят е логнат и токенът е валиден.
     */
    public function isAuthenticated(): bool
    {
        return SessionManager::isLoggedIn()
            && $this->jwt->haveJwt()
            && !$this->jwt->isExpired();
    }

    /**
     * Опит за логин чрез UUID (цикли през всички доставчици).
     */
    public function loginWithUuid(string $uuid): void
    {
        foreach ($this->providers as $provider) {
            $member = $provider->getMemberData($uuid);

            if ($member && $provider->isActiveMember($member)) {
                $this->login($member, $provider->getName());
                return;
            }
        }

        $this->logout('Invalid or inactive member.');
    }
    public function loginWithMsisdn(string $msisdn): void
    {
        foreach ($this->providers as $provider) {
            // Проверяваме дали доставчикът поддържа MSISDN логин
            if (method_exists($provider, 'getMemberByMsisdn')) {
                $member = $provider->getMemberByMsisdn($msisdn);

                if ($member && $provider->isActiveMember($member)) {
                    $this->login($member, $provider->getName());
                    return;
                }
            }
        }

        $this->logout('Invalid phone number or inactive subscription.');
    }

    /**
     * Вход в системата.
     */
    public function login(array $user, string $provider): void
    {
        // Създаваме сесия
        SessionManager::set('user_id', $user['id'] ?? $user['user_id'] ?? 0);
        SessionManager::set('provider', $provider);
        SessionManager::set('user', $user);

        // Създаваме JWT
        $this->jwt->createJWT(
            $user['msisdn'] ?? '',
            $user['service'] ?? 'main',
            $user['id'] ?? 0,
            $provider,
            608400,
            $_SERVER['HTTP_HOST'] ?? ''
        );
    }

    /**
     * Изход от системата.
     */
    public function logout(string $reason = null): void
    {
        if ($reason) {
            SessionManager::set('redirect_reason', $reason);
        }

        $this->jwt->removeJWT();
        SessionManager::destroy();
        $this->redirectToLogin();
    }

    /**
     * Връща данни за потребителя (от JWT).
     */
    public function user(): ?array
    {
        return $this->isAuthenticated() ? $this->jwt->getJwtInfo() : null;
    }

    /**
     * Пренасочване към login страница.
     */
    private function redirectToLogin(): void
    {
        header("Location: {$this->logoutPage}");
        exit;
    }

    /**
     * Добавяне на нов provider динамично.
     */
    public function addProvider(AuthProviderInterface $provider): void
    {
        $this->providers[] = $provider;
    }
}
