<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Performance index migration.
 *
 * Every index added here addresses a concrete, measured query pattern
 * identified in the filter layer (QueryFilter, model observers) and
 * service layer (UpcomingEventService, scoped LostItem queries, etc.).
 *
 * Index strategy per query type:
 *
 *   Exact match  (WHERE col = ?)        → single B-tree column index
 *   Date range   (WHERE col >= ? <= ?)  → single B-tree index, range scan
 *   ORDER BY     (ORDER BY col)         → single B-tree index, avoids filesort
 *   Co-filter    (WHERE a = ? AND b = ?)→ composite index (a, b)
 *   LIKE %term%  (full-text search)     → FULLTEXT index (MySQL) / GIN (Postgres)
 *
 * FULLTEXT indexes are applied conditionally — they require MySQL/MariaDB 5.6+
 * InnoDB or PostgreSQL GIN; SQLite (used in tests) does not support them.
 *
 * @see App\Filters\EventFilter
 * @see App\Filters\LostFoundFilter
 * @see App\Filters\RoomFilter
 * @see App\Filters\AcademicScheduleFilter
 * @see App\Services\Event\UpcomingEventService
 */
return new class extends Migration
{
    public function up(): void
    {
        $this->indexEvents();
        $this->indexBuildings();
        $this->indexRooms();
        $this->indexLostItems();
        $this->indexAcademicSchedules();
        $this->indexUsers();
        $this->indexNotifications();
        $this->applyFullTextIndexes();
    }

    public function down(): void
    {
        // Drop in reverse order of creation so FK-dependent tables come first.
        $this->dropFullTextIndexes();

        Schema::table('notifications',        fn (Blueprint $t) => $t->dropIndex('notifications_target_role_index'));
        Schema::table('notifications',        fn (Blueprint $t) => $t->dropIndex('notifications_created_at_index'));

        Schema::table('users',                fn (Blueprint $t) => $t->dropIndex('users_role_index'));

        Schema::table('academic_schedules',   fn (Blueprint $t) => $t->dropIndex('schedules_room_day_index'));
        Schema::table('academic_schedules',   fn (Blueprint $t) => $t->dropIndex('academic_schedules_start_time_index'));
        Schema::table('academic_schedules',   fn (Blueprint $t) => $t->dropIndex('academic_schedules_day_index'));

        Schema::table('lost_items',           fn (Blueprint $t) => $t->dropIndex('lost_items_status_created_at_index'));
        Schema::table('lost_items',           fn (Blueprint $t) => $t->dropIndex('lost_items_user_created_at_index'));
        Schema::table('lost_items',           fn (Blueprint $t) => $t->dropIndex('lost_items_user_status_index'));
        Schema::table('lost_items',           fn (Blueprint $t) => $t->dropIndex('lost_items_created_at_index'));
        Schema::table('lost_items',           fn (Blueprint $t) => $t->dropIndex('lost_items_status_index'));

        Schema::table('rooms',                fn (Blueprint $t) => $t->dropIndex('rooms_building_floor_index'));
        Schema::table('rooms',                fn (Blueprint $t) => $t->dropIndex('rooms_room_number_index'));
        Schema::table('rooms',                fn (Blueprint $t) => $t->dropIndex('rooms_floor_index'));

        Schema::table('buildings',            fn (Blueprint $t) => $t->dropIndex('buildings_name_index'));
        Schema::table('buildings',            fn (Blueprint $t) => $t->dropIndex('buildings_created_at_index'));

        Schema::table('events',               fn (Blueprint $t) => $t->dropIndex('events_created_at_index'));
        Schema::table('events',               fn (Blueprint $t) => $t->dropIndex('events_created_by_index'));
        Schema::table('events',               fn (Blueprint $t) => $t->dropIndex('events_time_range_index'));
        Schema::table('events',               fn (Blueprint $t) => $t->dropIndex('events_end_time_index'));
        Schema::table('events',               fn (Blueprint $t) => $t->dropIndex('events_start_time_index'));
    }

    // ─── Per-table index blocks ───────────────────────────────────────────────

    private function indexEvents(): void
    {
        Schema::table('events', function (Blueprint $table) {
            // EventFilter default sort + ORDER BY start_time → avoids filesort.
            $table->index('start_time', 'events_start_time_index');

            // Date range filter on end_time (?date_field=end_time).
            $table->index('end_time', 'events_end_time_index');

            // Composite (start_time, end_time):
            //   UpcomingEventService: WHERE start_time >= ? AND end_time >= ?
            //   Single index pass covers both predicates; eliminates full-table scan.
            $table->index(['start_time', 'end_time'], 'events_time_range_index');

            // FK: created_by → users.id
            //   MySQL creates an implicit index for FK constraints, but PostgreSQL
            //   does not. Explicit index ensures portability and covers
            //   joins/eager-loads on Event::with('creator').
            $table->index('created_by', 'events_created_by_index');

            // Sort fallback + ?sort_by=created_at.
            $table->index('created_at', 'events_created_at_index');
        });
    }

    private function indexBuildings(): void
    {
        Schema::table('buildings', function (Blueprint $table) {
            // Default sort is ORDER BY name ASC — index eliminates filesort.
            // Also covers a LIKE 'term%' prefix search on name.
            $table->index('name', 'buildings_name_index');

            // Sortable column via ?sort_by=created_at.
            $table->index('created_at', 'buildings_created_at_index');
        });
    }

    private function indexRooms(): void
    {
        Schema::table('rooms', function (Blueprint $table) {
            // Default sort + LIKE search on room_number.
            // Covers: ORDER BY room_number, WHERE room_number LIKE 'term%'.
            $table->index('room_number', 'rooms_room_number_index');

            // Exact filter via ?floor=2.
            $table->index('floor', 'rooms_floor_index');

            // Composite (building_id, floor):
            //   ?building_id=3&floor=2 → single index scan, no second lookup.
            //   Leading column building_id also serves as the FK index,
            //   satisfying JOIN performance for Room::with('building').
            $table->index(['building_id', 'floor'], 'rooms_building_floor_index');
        });
    }

    private function indexLostItems(): void
    {
        Schema::table('lost_items', function (Blueprint $table) {
            // Exact filter via ?status=lost|found.
            $table->index('status', 'lost_items_status_index');

            // Date range (?date_from, ?date_to) and default ORDER BY created_at DESC.
            $table->index('created_at', 'lost_items_created_at_index');

            // Composite (user_id, status):
            //   Scoped non-admin listing → WHERE user_id = ? AND status = ?
            //   Index covers both predicates in a single B-tree scan.
            $table->index(['user_id', 'status'], 'lost_items_user_status_index');

            // Composite (user_id, created_at):
            //   Scoped listing sorted by date → WHERE user_id = ? ORDER BY created_at DESC
            //   Avoids a second sort pass — index already ordered.
            $table->index(['user_id', 'created_at'], 'lost_items_user_created_at_index');

            // Composite (status, created_at):
            //   Admin listing filtered by status + sorted by date.
            //   WHERE status = ? ORDER BY created_at DESC → single index scan.
            $table->index(['status', 'created_at'], 'lost_items_status_created_at_index');
        });
    }

    private function indexAcademicSchedules(): void
    {
        Schema::table('academic_schedules', function (Blueprint $table) {
            // Default sort + exact day filter → ORDER BY day, WHERE day = ?
            $table->index('day', 'academic_schedules_day_index');

            // Sortable column: ?sort_by=start_time
            $table->index('start_time', 'academic_schedules_start_time_index');

            // Composite (room_id, day):
            //   ?room_id=5&day=Monday → schedule for a room on a specific day.
            //   Leading room_id satisfies the FK join; day narrows the scan.
            $table->index(['room_id', 'day'], 'schedules_room_day_index');
        });
    }

    private function indexUsers(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Role-based filtering in notification targeting, policy checks,
            // and admin dashboard user counts → WHERE role = ?
            $table->index('role', 'users_role_index');
        });
    }

    private function indexNotifications(): void
    {
        Schema::table('notifications', function (Blueprint $table) {
            // Notification dispatch queries: WHERE target_role IN ('student', 'all')
            $table->index('target_role', 'notifications_target_role_index');

            // Admin listing sort by newest first.
            $table->index('created_at', 'notifications_created_at_index');
        });
    }

    // ─── Full-text indexes ────────────────────────────────────────────────────

    /**
     * FULLTEXT indexes for LIKE '%term%' search patterns.
     *
     * The QueryFilter::applySearch() method generates:
     *   WHERE (col1 LIKE '%term%' OR col2 LIKE '%term%')
     *
     * A leading wildcard (%term%) cannot use a standard B-tree index —
     * the engine must scan every row. FULLTEXT indexes solve this by
     * maintaining an inverted word map.
     *
     * MySQL / MariaDB:  InnoDB FULLTEXT — O(1) word lookup.
     * PostgreSQL:       GIN index with tsvector — use a DB::statement directly.
     * SQLite (tests):   No FULLTEXT support — skipped via driver check.
     *
     * Query usage with FULLTEXT (optional future optimisation):
     *   Use MATCH(col) AGAINST(? IN BOOLEAN MODE) instead of LIKE.
     *   The current LIKE implementation will still benefit from FULLTEXT
     *   because MySQL's optimizer uses FULLTEXT when LIKE originates on
     *   a FULLTEXT-indexed column in some configurations (~5.7+).
     */
    private function applyFullTextIndexes(): void
    {
        $driver = DB::getDriverName();

        if ($driver === 'mysql' || $driver === 'mariadb') {
            $this->applyMysqlFullText();
        }

        if ($driver === 'pgsql') {
            $this->applyPostgresGin();
        }

        // SQLite does not support full-text indexing — skipped intentionally.
    }

    private function dropFullTextIndexes(): void
    {
        $driver = DB::getDriverName();

        if ($driver === 'mysql' || $driver === 'mariadb') {
            $this->dropMysqlFullText();
        }

        if ($driver === 'pgsql') {
            $this->dropPostgresGin();
        }
    }

    private function applyMysqlFullText(): void
    {
        Schema::table('events', function (Blueprint $table) {
            // Covers ?q= search on title + location.
            // description is text — excluded to keep index compact;
            // add it here if description search is high-frequency.
            $table->fullText(['title', 'location'], 'events_fulltext');
        });

        Schema::table('buildings', function (Blueprint $table) {
            $table->fullText(['name'], 'buildings_fulltext');
        });

        Schema::table('lost_items', function (Blueprint $table) {
            $table->fullText(['title', 'location'], 'lost_items_fulltext');
        });

        Schema::table('academic_schedules', function (Blueprint $table) {
            $table->fullText(['course_name'], 'schedules_fulltext');
        });
    }

    private function dropMysqlFullText(): void
    {
        Schema::table('events',             fn (Blueprint $t) => $t->dropFullText('events_fulltext'));
        Schema::table('buildings',          fn (Blueprint $t) => $t->dropFullText('buildings_fulltext'));
        Schema::table('lost_items',         fn (Blueprint $t) => $t->dropFullText('lost_items_fulltext'));
        Schema::table('academic_schedules', fn (Blueprint $t) => $t->dropFullText('schedules_fulltext'));
    }

    private function applyPostgresGin(): void
    {
        // GIN indexes on tsvector columns are the PostgreSQL equivalent.
        // Uses pg_catalog.english — change to 'simple' for multilingual content.
        DB::statement("CREATE INDEX events_gin ON events USING gin(to_tsvector('english', coalesce(title,'') || ' ' || coalesce(location,'')))");
        DB::statement("CREATE INDEX buildings_gin ON buildings USING gin(to_tsvector('english', coalesce(name,'')))");
        DB::statement("CREATE INDEX lost_items_gin ON lost_items USING gin(to_tsvector('english', coalesce(title,'') || ' ' || coalesce(location,'')))");
        DB::statement("CREATE INDEX schedules_gin ON academic_schedules USING gin(to_tsvector('english', coalesce(course_name,'')))");
    }

    private function dropPostgresGin(): void
    {
        DB::statement('DROP INDEX IF EXISTS events_gin');
        DB::statement('DROP INDEX IF EXISTS buildings_gin');
        DB::statement('DROP INDEX IF EXISTS lost_items_gin');
        DB::statement('DROP INDEX IF EXISTS schedules_gin');
    }
};
