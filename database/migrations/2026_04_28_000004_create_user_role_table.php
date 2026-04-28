<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (!Schema::hasTable('user_role')) {
            Schema::create('user_role', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
                $table->foreignId('role_id')->constrained('roles')->cascadeOnDelete();
                $table->timestamps();

                $table->unique(['user_id', 'role_id']);
            });
        }

        if (!Schema::hasTable('roles') || !Schema::hasColumn('users', 'role')) {
            return;
        }

        $now = now();
        $roleNames = DB::table('users')
            ->whereNotNull('role')
            ->select('role')
            ->distinct()
            ->pluck('role')
            ->filter(fn ($value) => is_string($value) && trim($value) !== '')
            ->map(fn (string $value) => trim($value))
            ->values();

        foreach ($roleNames as $roleName) {
            DB::table('roles')->updateOrInsert(
                ['name' => $roleName],
                ['updated_at' => $now, 'created_at' => $now]
            );
        }

        $roleIdMap = DB::table('roles')->pluck('id', 'name');

        DB::table('users')
            ->whereNotNull('role')
            ->select('id', 'role')
            ->orderBy('id')
            ->chunk(500, function ($users) use ($roleIdMap, $now) {
                foreach ($users as $user) {
                    $roleName = is_string($user->role) ? trim($user->role) : '';
                    $roleId = $roleIdMap[$roleName] ?? null;

                    if (!$roleId) {
                        continue;
                    }

                    DB::table('user_role')->updateOrInsert(
                        ['user_id' => $user->id, 'role_id' => $roleId],
                        ['updated_at' => $now, 'created_at' => $now]
                    );
                }
            });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_role');
    }
};
