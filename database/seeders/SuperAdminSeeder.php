<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class SuperAdminSeeder extends Seeder
{
    public function run(): void
    {
        $this->command->info("🔥 SuperAdminSeeder is running...");
        $email = env('SUPER_ADMIN_EMAIL', 'test0test12034@gmail.com');

        if (User::where('email', $email)->exists()) {
            $this->command->warn("Super admin [{$email}] already exists. Skipping.");
            return;
        }

        User::create([
            'name'              => 'Super Admin',
            'email'             => $email,
            'password'          => Hash::make(env('SUPER_ADMIN_PASSWORD', 'Sup3rS3cure!')),
            'role'              => 'super_admin',
            'is_verified'       => true,
            'email_verified_at' => now(),
        ]);

        $this->command->info("Super admin created: {$email}");
    }
}
