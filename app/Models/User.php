<?php

namespace App\Models;

use App\Enums\UserRole;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use PHPOpenSourceSaver\JWTAuth\Contracts\JWTSubject;

class User extends Authenticatable implements JWTSubject
{
    use HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'is_verified',
        'email_verified_at',
        'notification_preferences',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
        'is_verified' => 'boolean',
        'role' => UserRole::class,
    ];

    public function lostItems()
    {
        return $this->hasMany(LostItem::class);
    }

    public function itemClaims()
    {
        return $this->hasMany(ItemClaim::class);
    }

    public function events()
    {
        return $this->hasMany(Event::class, 'created_by');
    }

    public function registeredEvents()
    {
        return $this->belongsToMany(Event::class, 'event_user')
            ->withTimestamps();
    }

    /**
     * Device tokens registered for push notifications.
     */
    public function deviceTokens()
    {
        return $this->hasMany(DeviceToken::class);
    }

    /**
     * Notifications sent by this user (if they are an admin).
     */
    public function notificationsSent()
    {
        return $this->hasMany(Notification::class, 'sender_id');
    }

    /**
     * Notification recipients records for this user.
     */
    public function notificationRecipients()
    {
        return $this->hasMany(NotificationRecipient::class);
    }

    /**
     * Push notification preferences stored as a JSON object.
     *
     * Returns an array with defaults merged in so callers never need
     * to null-check individual keys:
     *   'enabled'   => true
     *   'reminders' => ['24h', '1h', '10min']
     *   'locale'    => 'en'
     */
    public function getNotificationPreferencesAttribute(?string $value): array
    {
        $defaults = [
            'enabled'   => true,
            'reminders' => ['24h', '1h', '10min'],
            'locale'    => 'en',
        ];

        if ($value === null) {
            return $defaults;
        }

        $stored = json_decode($value, true) ?? [];

        return array_merge($defaults, $stored);
    }
        
    public function getJWTIdentifier()
    {
        return $this->getKey();
    }

    public function getJWTCustomClaims(): array
    {
        return [];
    }

    public function roleEnum(): UserRole
    {
        if ($this->role instanceof UserRole) {
            return $this->role;
        }

        return UserRole::tryFrom((string) $this->role) ?? UserRole::User;
    }

    public function hasRole(UserRole|string $role): bool
    {
        $expected = $role instanceof UserRole ? $role : UserRole::tryFrom($role);

        if ($expected === null) {
            return false;
        }

        return $this->roleEnum() === $expected;
    }

    /**
     * @param  array<int, UserRole|string>  $roles
     */
    public function hasAnyRole(array $roles): bool
    {
        foreach ($roles as $role) {
            if ($this->hasRole($role)) {
                return true;
            }
        }

        return false;
    }

    public function isSuperAdmin(): bool
    {
        return $this->hasRole(UserRole::SuperAdmin);
    }

    public function isUser(): bool
    {
        return $this->hasRole(UserRole::User);
    }
}