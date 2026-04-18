<?php

namespace App\Services;

use App\Models\DeviceToken;

class DeviceTokenService
{
    public function saveToken(int $userId, array $data): DeviceToken
    {
        return DeviceToken::updateOrCreate(
            ['token' => $data['token']],
            [
                'user_id' => $userId,
                'platform' => $data['platform'],
                'last_used_at' => now(),
            ]
        );
    }

    public function removeToken(int $userId, string $token): void
    {
        DeviceToken::query()
            ->where('token', $token)
            ->where('user_id', $userId)
            ->delete();
    }
}
