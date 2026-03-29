<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Indexing notes:
     * - `type` is a low-cardinality string column (e.g. lecture_hall, lab, classroom).
     *   An index is beneficial when queries filter rooms by type (e.g. show all labs
     *   in a building). The composite index on (building_id, type) is especially
     *   useful for queries like: "all labs in building 3".
     */
    public function up(): void
    {
        Schema::table('rooms', function (Blueprint $table) {
            // Maximum occupancy of the room
            $table->unsignedInteger('capacity')->default(0)->after('floor');

            // Category/type of the room
            // Examples: lecture_hall, lab, meeting_room, auditorium, classroom
            $table->string('type')->default('classroom')->after('capacity');

            // Index `type` alone for queries filtering only by type
            $table->index('type', 'rooms_type_index');

            // Composite index for the most common query pattern:
            // all rooms of a given type within a specific building
            $table->index(['building_id', 'type'], 'rooms_building_type_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('rooms', function (Blueprint $table) {
            $table->dropIndex('rooms_building_type_index');
            $table->dropIndex('rooms_type_index');
            $table->dropColumn(['capacity', 'type']);
        });
    }
};
