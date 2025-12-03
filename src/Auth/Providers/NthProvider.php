<?php
namespace App\Auth\Providers;

use App\Auth\AuthProviderInterface;
use App\nthMember;

class NthProvider implements AuthProviderInterface
{
    private nthMember $nth;

    public function __construct()
    {
        $this->nth = new nthMember();
    }

    public function getName(): string
    {
        return 'Nth';
    }

    /**
     * Извлича потребител по public_uuid.
     */
    public function getMemberDataByUuid(string $uuid): ?array
    {
        $member = $this->nth->getNthMemberByPublicUuid($uuid);

        if (empty($member) || $member === 'error') {
            return null;
        }

        return [
            'id' => $member['id'] ?? null,
            'msisdn' => $member['msisdn'] ?? null,
            'public_uuid' => $member['public_uuid'] ?? null,
            'event' => $member['event'] ?? null,
            'created_at' => $member['created_at'] ?? null,
            'status' => $member['status'] ?? null,
        ];
    }

    /**
     * Проверява дали потребителят има активен абонамент.
     */
    public function getMemberDataByMsisdn(string $msisdn): ?array
    {
        $member = $this->nth->GetNthMemberByMsisdn($msisdn);
        return (empty($member) || $member === 'error') ? null : $member;
    }

    public function isActiveMember(array $member): bool
    {
        if (!$member) return false;

        $status = strtolower($member['event'] ?? '');
        return in_array($status, ['sub_open', 'active']);
    }
}
