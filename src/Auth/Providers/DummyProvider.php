<?php

namespace App\Auth\Providers;

use App\Auth\AuthProviderInterface;

class DummyProvider implements AuthProviderInterface
{
    private array $members = [
        [
            'id'          => 1,
            'msisdn'      => '359881234567',
            'public_uuid' => 'dummy-uuid-0001',
            'service'     => 'main',
            'event'       => 'sub_open',
            'status'      => 'active',
            'created_at'  => '2025-01-01 00:00:00',
        ],
        [
            'id'          => 2,
            'msisdn'      => '359887654321',
            'public_uuid' => 'dummy-uuid-0002',
            'service'     => 'main',
            'event'       => 'sub_close',
            'status'      => 'inactive',
            'created_at'  => '2025-01-01 00:00:00',
        ],
    ];

    public function getName(): string
    {
        return 'Dummy';
    }

    public function getMemberDataByUuid(string $uuid): ?array
    {
        foreach ($this->members as $member) {
            if ($member['public_uuid'] === $uuid) {
                return $member;
            }
        }
        return null;
    }

    public function getMemberDataByMsisdn(string $msisdn): ?array
    {
        foreach ($this->members as $member) {
            if ($member['msisdn'] === $msisdn) {
                return $member;
            }
        }
        return null;
    }

    public function isActiveMember(array $member): bool
    {
        $status = strtolower($member['event'] ?? '');
        return in_array($status, ['sub_open', 'active']);
    }
}