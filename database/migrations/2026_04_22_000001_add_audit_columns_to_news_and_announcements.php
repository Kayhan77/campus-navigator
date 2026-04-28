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
        Schema::table('news', function (Blueprint $table): void {
            $table->foreignId('created_by')->nullable()->after('published_at')->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->after('created_by')->constrained('users')->nullOnDelete();
            $table->foreignId('published_by')->nullable()->after('updated_by')->constrained('users')->nullOnDelete();
        });

        Schema::table('announcements', function (Blueprint $table): void {
            $table->foreignId('created_by')->nullable()->after('published_at')->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->after('created_by')->constrained('users')->nullOnDelete();
            $table->foreignId('published_by')->nullable()->after('updated_by')->constrained('users')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('news', function (Blueprint $table): void {
            $table->dropForeign(['created_by']);
            $table->dropForeign(['updated_by']);
            $table->dropForeign(['published_by']);
            $table->dropColumn(['created_by', 'updated_by', 'published_by']);
        });

        Schema::table('announcements', function (Blueprint $table): void {
            $table->dropForeign(['created_by']);
            $table->dropForeign(['updated_by']);
            $table->dropForeign(['published_by']);
            $table->dropColumn(['created_by', 'updated_by', 'published_by']);
        });
    }
};
