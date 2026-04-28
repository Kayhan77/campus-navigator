<?php

namespace Database\Seeders;

use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;

class RbacSeeder extends Seeder
{
    public function run(): void
    {
        $roles = ['super_admin', 'admin', 'sub_admin'];
        $permissions = [
            'create_event',
            'create_news',
            'create_announcement',
            'send_notification',
            'manage_users',
        ];

        foreach ($roles as $roleName) {
            Role::query()->firstOrCreate(['name' => $roleName]);
        }

        foreach ($permissions as $permissionName) {
            Permission::query()->firstOrCreate(['name' => $permissionName]);
        }

        $permissionIds = Permission::query()->pluck('id', 'name');

        $superAdmin = Role::query()->where('name', 'super_admin')->first();
        $admin = Role::query()->where('name', 'admin')->first();
        $subAdmin = Role::query()->where('name', 'sub_admin')->first();

        if ($superAdmin) {
            $superAdmin->permissions()->sync($permissionIds->values()->all());
        }

        if ($admin) {
            $admin->permissions()->sync(array_values(array_filter([
                $permissionIds['create_event'] ?? null,
                $permissionIds['create_news'] ?? null,
                $permissionIds['create_announcement'] ?? null,
                $permissionIds['send_notification'] ?? null,
            ])));
        }

        if ($subAdmin) {
            $subAdmin->permissions()->sync(array_values(array_filter([
                $permissionIds['create_event'] ?? null,
                $permissionIds['create_news'] ?? null,
            ])));
        }

        // Map existing users.role values to user_role pivot for backward compatibility.
        User::query()
            ->whereNotNull('role')
            ->select(['id', 'role'])
            ->orderBy('id')
            ->chunk(500, function ($users): void {
                foreach ($users as $user) {
                    $roleName = is_string($user->role) ? $user->role : (string) $user->role?->value;
                    if (!$roleName) {
                        continue;
                    }

                    $role = Role::query()->firstOrCreate(['name' => $roleName]);
                    $user->roles()->sync([$role->id]);
                }
            });
    }
}
