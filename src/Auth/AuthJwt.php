<?php

namespace App\Auth;

use Firebase\JWT\JWT;
use Firebase\JWT\Key;

class AuthJwt
{
    /*
    1 седмица = 7 дни
    1 ден = 24 часа
    1 час = 60 минути
    1 минута = 60 секунди
    1 седмица = 7×24×60×60 = 604800 секунди.
    1 седмица и 1 час =      608400 секунди
    */
    private string $secretKey;
    private string $algorithm;
    private string $tokenName = 'auth_token';
    private int    $lifetime  = 608400; // 1 седмица и 1 час

    private ?string $jwt = null;

    public function __construct(string $secretKey)
    {
        $this->secretKey = $secretKey;
        $this->algorithm = 'HS256';
    }

    public function haveJwt(): bool
    {
        $this->_getJwt();
        return !empty($this->jwt);
    }

    private function _getJwt(): void
    {
        $this->jwt = $_COOKIE[$this->tokenName] ?? null;
    }

    public function createJWT(
        string $msisdn,
        string $service,
        int    $userId = 0,
        string $provider = 'unknown',
        int    $validityPeriodSeconds = 608400,
        string $domain = ''
    ): void {
        $payload = [
            'member_msisdn' => $msisdn,
            'service'       => $service,
            'user_id'       => $userId,
            'provider'      => $provider,
            'domain'        => $domain,
            'issued_at'     => time(),
            'expires_at'    => time() + $validityPeriodSeconds,
        ];

        $this->_encode($payload);
        $this->_setJwt($validityPeriodSeconds);
    }

    public function removeJWT(): void
    {
        $this->_unsetJwt();
    }

    private function _encode(array $payload): void
    {
        $this->jwt = JWT::encode($payload, $this->secretKey, $this->algorithm);
    }

    public function decode(): object
    {
        return JWT::decode($this->jwt, new Key($this->secretKey, $this->algorithm));
    }

    private function _setJwt(int $validityPeriodSeconds): void
    {
        setcookie($this->tokenName, $this->jwt, time() + $validityPeriodSeconds, '/', '', true, true);
    }

    private function _unsetJwt(): void
    {
        setcookie($this->tokenName, '', time() - 3600, '/');
    }

    public function isExpired(): bool
    {
        try {
            $decoded   = $this->decode();
            $expiresAt = $decoded->expires_at ?? 0;
            return time() > $expiresAt;
        } catch (\Exception $e) {
            return true;
        }
    }

    public function getInfo(): array
    {
        try {
            $decoded   = $this->decode();
            $expiresAt = $decoded->expires_at ?? 0;
            $issuedAt  = $decoded->issued_at  ?? 0;

            $remainingTime = max($expiresAt - time(), 0);

            $days    = floor($remainingTime / 86400);
            $hours   = floor(($remainingTime % 86400) / 3600);
            $minutes = floor(($remainingTime % 3600) / 60);
            $seconds = $remainingTime % 60;

            $humanReadable = $days > 0
                ? sprintf('%d days %02d Hours %02d Minutes %02d Seconds', $days, $hours, $minutes, $seconds)
                : sprintf('%02d Hours %02d Minutes %02d Seconds', $hours, $minutes, $seconds);

            return [
                'msisdn'         => $decoded->member_msisdn ?? null,
                'domain'         => $decoded->domain        ?? null,
                'user_id'        => $decoded->user_id       ?? null,
                'provider'       => $decoded->provider      ?? null,
                'issued_at'      => date('Y-m-d H:i:s', $issuedAt),
                'expires_at'     => date('Y-m-d H:i:s', $expiresAt),
                'remaining_time' => $humanReadable,
            ];
        } catch (\Exception $e) {
            return ['error' => 'Invalid or expired token'];
        }
    }
}