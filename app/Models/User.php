<?php

namespace App\Models;

use App\Enums\UserRole;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Builder;
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

    public function newsCreated()
    {
        return $this->hasMany(News::class, 'created_by');
    }

    public function newsUpdated()
    {
        return $this->hasMany(News::class, 'updated_by');
    }

    public function newsPublished()
    {
        return $this->hasMany(News::class, 'published_by');
    }

    public function announcementsCreated()
    {
        return $this->hasMany(Announcement::class, 'created_by');
    }

    public function announcementsUpdated()
    {
        return $this->hasMany(Announcement::class, 'updated_by');
    }

    public function announcementsPublished()
    {
        return $this->hasMany(Announcement::class, 'published_by');
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

    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class, 'user_role')
            ->withTimestamps();
    }

    public function permissions(): Builder
    {
        return Permission::query()->whereHas('roles.users', function (Builder $query): void {
            $query->where('users.id', $this->getKey());
        });
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
        $roleValue = $role instanceof UserRole ? $role->value : trim((string) $role);

        if ($roleValue === '') {
            return false;
        }

        if ($this->roleEnum()->value === $roleValue) {
            return true;
        }

        if ($this->relationLoaded('roles')) {
            return $this->roles->contains('name', $roleValue);
        }

        return $this->roles()->where('name', $roleValue)->exists();
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

    public function hasPermission(string $permission): bool
    {
        $permission = trim($permission);

        if ($permission === '') {
            return false;
        }

        if ($this->hasRole(UserRole::SuperAdmin)) {
            return true;
        }

        if ($this->permissions()->where('name', $permission)->exists()) {
            return true;
        }

        return in_array($permission, $this->legacyPermissionMap(), true);
    }

    public function isSuperAdmin(): bool
    {
        return $this->hasRole(UserRole::SuperAdmin);
    }

    public function isUser(): bool
    {
        return $this->hasRole(UserRole::User);
    }

    /**
     * Legacy role/is_admin compatibility layer while pivot-based RBAC is adopted.
     *
     * @return array<int, string>
     */
    private function legacyPermissionMap(): array
    {
        if ((bool) ($this->attributes['is_admin'] ?? false)) {
            return [
                'create_event',
                'create_news',
                'create_announcement',
                'send_notification',
                'manage_users',
            ];
        }

        return match ($this->roleEnum()) {
            UserRole::SuperAdmin => [
                'create_event',
                'create_news',
                'create_announcement',
                'send_notification',
                'manage_users',
            ],
            UserRole::Admin => [
                'create_event',
                'create_news',
                'create_announcement',
                'send_notification',
            ],
            UserRole::SubAdmin => [],
            default => [],
        };
    }
}
