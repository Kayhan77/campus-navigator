<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Migrate existing 'student' roles to 'user'
        DB::table('users')->where('role', 'student')->update(['role' => 'user']);

        // Change column default to 'user' and restrict to valid enum values
        Schema::table('users', function (Blueprint $table) {
            $table->string('role')->default('user')->change();
        });
    }

    public function down(): void
    {
        DB::table('users')->where('role', 'user')->update(['role' => 'student']);

        Schema::table('users', function (Blueprint $table) {
            $table->string('role')->default('student')->change();
        });
    }
};
