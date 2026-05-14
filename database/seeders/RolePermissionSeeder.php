<?php

namespace Database\Seeders;

use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Seeder;

class RolePermissionSeeder extends Seeder
{
    public function run(): void
    {
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
    }
}
