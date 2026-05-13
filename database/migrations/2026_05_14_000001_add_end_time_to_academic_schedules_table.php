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
        if (! Schema::hasColumn('academic_schedules', 'end_time')) {
            Schema::table('academic_schedules', function (Blueprint $table) {
                $table->time('end_time')->nullable();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasColumn('academic_schedules', 'end_time')) {
            Schema::table('academic_schedules', function (Blueprint $table) {
                $table->dropColumn('end_time');
            });
        }
    }
};
