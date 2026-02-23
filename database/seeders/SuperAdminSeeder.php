<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class SuperAdminSeeder extends Seeder
{
    public function run(): void
    {
        $email = env('SUPER_ADMIN_EMAIL', 'superadmin@campus.local');

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
