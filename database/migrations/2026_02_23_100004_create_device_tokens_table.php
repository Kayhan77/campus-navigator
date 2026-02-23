<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('device_tokens', function (Blueprint $table) {
            $table->id();

            // Nullable so tokens can be stored before login (then associated on auth)
            $table->foreignId('user_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->string('token', 255)->unique();
            $table->string('platform', 20)->nullable()->comment('android|ios|web');
            $table->timestamp('last_used_at')->nullable();
            $table->timestamps();

            // Composite index for fast user-token lookups
            $table->index(['user_id', 'platform']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('device_tokens');
    }
};
