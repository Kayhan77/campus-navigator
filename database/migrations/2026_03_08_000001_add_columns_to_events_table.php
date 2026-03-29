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
        Schema::table('events', function (Blueprint $table) {
            // room_id already exists on this table — skipped.

            // Free-text fallback when no room is assigned
            $table->string('location_override')->nullable()->after('room_id');

            // Lifecycle status — indexed for common filter queries
            $table->enum('status', ['draft', 'published', 'cancelled', 'completed'])
                  ->default('draft')
                  ->after('location_override');

            // Visibility flag
            $table->boolean('is_public')->default(true)->after('status');

            // Attendance limits
            $table->unsignedInteger('max_attendees')->nullable()->after('is_public');

            // Registration requirement
            $table->boolean('registration_required')->default(false)->after('max_attendees');

            // Tracks when reminder notifications were dispatched
            $table->timestamp('reminder_sent_at')->nullable()->after('registration_required');

            // Index status for efficient filtering (e.g. published events)
            $table->index('status', 'events_status_index');

            // Composite index for calendar queries on published/public events
            $table->index(['status', 'start_time'], 'events_status_start_time_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('events', function (Blueprint $table) {
            $table->dropIndex('events_status_start_time_index');
            $table->dropIndex('events_status_index');
            $table->dropColumn([
                'location_override',
                'status',
                'is_public',
                'max_attendees',
                'registration_required',
                'reminder_sent_at',
            ]);
        });
    }
};
