<?php

namespace App\Auth;

interface AuthProviderInterface
{
    public function getName(): string;

    public function getMemberDataByUuid(string $uuid): ?array;

    public function getMemberDataByMsisdn(string $msisdn): ?array;

    public function isActiveMember(array $member): bool;
}
