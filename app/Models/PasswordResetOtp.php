<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PasswordResetOtp extends Model
{
    protected $fillable = [
        'email',
        'otp_hash',
        'expires_at',
        'attempts',
        'last_sent_at',
    ];

    protected $casts = [
        'expires_at'   => 'datetime',
        'last_sent_at' => 'datetime',
        'attempts'     => 'integer',
    ];

    protected $hidden = [
        'otp_hash',
    ];

    public function isExpired(): bool
    {
        return $this->expires_at->isPast();
    }

    public function isMaxAttemptsReached(): bool
    {
        return $this->attempts >= 5;
    }

    public function isOnCooldown(): bool
    {
        return $this->last_sent_at !== null
            && $this->last_sent_at->diffInSeconds(now()) < 30;
    }

    public function cooldownRemaining(): int
    {
        if ($this->last_sent_at === null) {
            return 0;
        }

        $remaining = 30 - (int) $this->last_sent_at->diffInSeconds(now());

        return max(0, $remaining);
    }
}
