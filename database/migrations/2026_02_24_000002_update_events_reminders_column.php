<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Replace the single `reminder_sent_at` column with a JSON
     * `reminders_dispatched` array that tracks each reminder window
     * independently (e.g. ["24h", "1h", "10min"]).
     *
     * This enables multiple reminder times per event without
     * duplicating rows or complex flag columns.
     */
    public function up(): void
    {
        // Step 1: Drop the composite index that references reminder_sent_at.
        // Must be a separate call from the column drop on SQLite — SQLite
        // recreates the whole table when dropping a column, and would fail
        // trying to re-apply an index that references the dropped column.
        Schema::table('events', function (Blueprint $table) {
            try {
                $table->dropIndex('events_start_time_reminder_sent_at_index');
            } catch (\Throwable) {
                // Index may not exist in all environments
            }
        });

        // Step 2: Drop the old single-window flag and add the JSON array.
        Schema::table('events', function (Blueprint $table) {
            if (Schema::hasColumn('events', 'reminder_sent_at')) {
                $table->dropColumn('reminder_sent_at');
            }

            if (! Schema::hasColumn('events', 'reminders_dispatched')) {
                $table->json('reminders_dispatched')->nullable()->after('end_time')
                    ->comment('Array of dispatched reminder keys, e.g. ["24h","1h"]');
            }
        });
    }

    public function down(): void
    {
        Schema::table('events', function (Blueprint $table) {
            $table->dropColumn('reminders_dispatched');
            $table->timestamp('reminder_sent_at')->nullable();
        });
    }
};
