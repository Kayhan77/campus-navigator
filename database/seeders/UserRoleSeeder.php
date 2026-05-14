<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;

class UserRoleSeeder extends Seeder
{
    public function run(): void
    {
        User::query()
            ->whereNotNull('role')
            ->select(['id', 'role'])
            ->orderBy('id')
            ->chunk(500, function ($users): void {
                foreach ($users as $user) {
                    $roleName = is_string($user->role) ? $user->role : (string) $user->role?->value;

                    if (! $roleName) {
                        continue;
                    }

                    $role = Role::query()->firstOrCreate(['name' => $roleName]);
                    $user->roles()->syncWithoutDetaching([$role->id]);
                }
            });
    }
}
