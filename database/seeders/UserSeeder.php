<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        $this->command->info("🔹 UserSeeder is running...");

        $email = env('USER_EMAIL', 'fortest12034@gmail.com');

        if (User::where('email', $email)->exists()) {
            $this->command->warn("User [{$email}] already exists. Skipping.");
            return;
        }

        $user = User::create([
            'name'              => 'Test User',
            'email'             => $email,
            'password'          => Hash::make(env('USER_PASSWORD', '121212')),
            'role'              => 'user',
            'is_verified'       => true,
            'email_verified_at' => now(),
        ]);

        $role = Role::query()->firstOrCreate(['name' => 'user']);
        $user->roles()->sync([$role->id]);

        $this->command->info("User created: {$email}");
    }
}
