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
        if (! Schema::hasColumn('academic_schedules', 'course_name')) {
            Schema::table('academic_schedules', function (Blueprint $table) {
                $table->string('course_name')->nullable();
            });
        }

        if (! Schema::hasColumn('academic_schedules', 'day')) {
            Schema::table('academic_schedules', function (Blueprint $table) {
                $table->string('day')->nullable();
            });
        }

        if (! Schema::hasColumn('academic_schedules', 'start_time')) {
            Schema::table('academic_schedules', function (Blueprint $table) {
                $table->time('start_time')->nullable();
            });
        }

        if (! Schema::hasColumn('academic_schedules', 'room_id')) {
            Schema::table('academic_schedules', function (Blueprint $table) {
                $table->foreignId('room_id')
                    ->nullable()
                    ->constrained('rooms')
                    ->nullOnDelete();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasColumn('academic_schedules', 'room_id')) {
            Schema::table('academic_schedules', function (Blueprint $table) {
                try {
                    $table->dropForeign(['room_id']);
                } catch (\Throwable $e) {
                    // Column may exist without an FK if the schema was manually altered.
                }

                $table->dropColumn('room_id');
            });
        }

        if (Schema::hasColumn('academic_schedules', 'start_time')) {
            Schema::table('academic_schedules', function (Blueprint $table) {
                $table->dropColumn('start_time');
            });
        }

        if (Schema::hasColumn('academic_schedules', 'day')) {
            Schema::table('academic_schedules', function (Blueprint $table) {
                $table->dropColumn('day');
            });
        }

        if (Schema::hasColumn('academic_schedules', 'course_name')) {
            Schema::table('academic_schedules', function (Blueprint $table) {
                $table->dropColumn('course_name');
            });
        }
    }
};
