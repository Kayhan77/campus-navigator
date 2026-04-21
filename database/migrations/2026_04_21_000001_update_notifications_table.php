<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('notifications', function (Blueprint $table) {
            // Add new columns if they don't exist
            if (!Schema::hasColumn('notifications', 'type')) {
                $table->string('type')->default('admin')->after('message');
            }
            if (!Schema::hasColumn('notifications', 'data')) {
                $table->json('data')->nullable()->after('type');
            }
            if (!Schema::hasColumn('notifications', 'sender_id')) {
                $table->foreignId('sender_id')->nullable()->constrained('users')->cascadeOnDelete()->after('data');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('notifications', function (Blueprint $table) {
            if (Schema::hasColumn('notifications', 'sender_id')) {
                $table->dropForeignKeyIfExists(['sender_id']);
                $table->dropColumn('sender_id');
            }
            if (Schema::hasColumn('notifications', 'data')) {
                $table->dropColumn('data');
            }
            if (Schema::hasColumn('notifications', 'type')) {
                $table->dropColumn('type');
            }
        });
    }
};
