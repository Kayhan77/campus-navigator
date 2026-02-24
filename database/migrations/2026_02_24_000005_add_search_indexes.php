<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Add database indexes to support the search and filter system.
 *
 * Indexes are added for every column that appears in a filter's
 * $searchable, $sortable, or $allowedFilters arrays, giving the
 * query planner efficient access paths for LIKE prefix queries,
 * equality filters, ORDER BY clauses, and date-range scans.
 *
 * NOTE: LIKE '%…%' (leading wildcard) cannot use a B-tree index;
 * the indexes here still benefit equality filters, range scans, and
 * sorts, and set the project up for a future FULLTEXT upgrade.
 */
return new class extends Migration
{
    public function up(): void
    {
        // ── events ────────────────────────────────────────────────────────────
        Schema::table('events', function (Blueprint $table) {
            // Filter / sort targets
            $table->index('title',      'idx_events_title');
            $table->index('location',   'idx_events_location');
            $table->index('start_time', 'idx_events_start_time');
            $table->index('end_time',   'idx_events_end_time');
            $table->index('created_by', 'idx_events_created_by');
        });

        // ── buildings ─────────────────────────────────────────────────────────
        Schema::table('buildings', function (Blueprint $table) {
            $table->index('name', 'idx_buildings_name');
        });

        // ── rooms ─────────────────────────────────────────────────────────────
        Schema::table('rooms', function (Blueprint $table) {
            $table->index('room_number',  'idx_rooms_room_number');
            $table->index('floor',        'idx_rooms_floor');
            // building_id is a FK but adding an explicit index ensures it is
            // used for the allowedFilter equality check on non-InnoDB engines.
            $table->index('building_id',  'idx_rooms_building_id');
        });

        // ── lost_items ────────────────────────────────────────────────────────
        Schema::table('lost_items', function (Blueprint $table) {
            $table->index('status',   'idx_lost_items_status');
            $table->index('location', 'idx_lost_items_location');
            $table->index('user_id',  'idx_lost_items_user_id');
        });

        // ── academic_schedules ────────────────────────────────────────────────
        Schema::table('academic_schedules', function (Blueprint $table) {
            $table->index('course_name', 'idx_academic_schedules_course_name');
            $table->index('day',         'idx_academic_schedules_day');
            $table->index('start_time',  'idx_academic_schedules_start_time');
            $table->index('room_id',     'idx_academic_schedules_room_id');
        });
    }

    public function down(): void
    {
        Schema::table('events', function (Blueprint $table) {
            $table->dropIndex('idx_events_title');
            $table->dropIndex('idx_events_location');
            $table->dropIndex('idx_events_start_time');
            $table->dropIndex('idx_events_end_time');
            $table->dropIndex('idx_events_created_by');
        });

        Schema::table('buildings', function (Blueprint $table) {
            $table->dropIndex('idx_buildings_name');
        });

        Schema::table('rooms', function (Blueprint $table) {
            $table->dropIndex('idx_rooms_room_number');
            $table->dropIndex('idx_rooms_floor');
            $table->dropIndex('idx_rooms_building_id');
        });

        Schema::table('lost_items', function (Blueprint $table) {
            $table->dropIndex('idx_lost_items_status');
            $table->dropIndex('idx_lost_items_location');
            $table->dropIndex('idx_lost_items_user_id');
        });

        Schema::table('academic_schedules', function (Blueprint $table) {
            $table->dropIndex('idx_academic_schedules_course_name');
            $table->dropIndex('idx_academic_schedules_day');
            $table->dropIndex('idx_academic_schedules_start_time');
            $table->dropIndex('idx_academic_schedules_room_id');
        });
    }
};
