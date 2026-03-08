<?php

namespace App\Enums;

enum UserRole: string
{
    case User = 'user';
    case Admin = 'admin';
    case SuperAdmin = 'super_admin';

    /**
     * @return array<int, string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    /**
     * Roles with administrative privileges.
     *
     * @return array<int, self>
     */
    public static function adminRoles(): array
    {
        return [self::Admin, self::SuperAdmin];
    }

    public function isAdminLevel(): bool
    {
        return in_array($this, self::adminRoles(), true);
    }
}
