<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('events', function (Blueprint $table) {
            // Tracks when reminder was sent — null means not yet dispatched
            $table->timestamp('reminder_sent_at')->nullable()->after('end_time');
            // Index for efficient scheduler query
            $table->index(['start_time', 'reminder_sent_at']);
        });
    }

    public function down(): void
    {
        Schema::table('events', function (Blueprint $table) {
            $table->dropIndex(['start_time', 'reminder_sent_at']);
            $table->dropColumn('reminder_sent_at');
        });
    }
};
