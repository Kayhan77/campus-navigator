<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('buildings', function (Blueprint $table) {
            $table->string('type')->default('building')->after('name');
            $table->string('category')->nullable()->after('type');
            $table->string('image')->nullable()->after('description');
            $table->string('opening_hours')->nullable()->after('image');
            $table->string('phone')->nullable()->after('opening_hours');
            $table->text('notes')->nullable()->after('phone');
        });
    }

    public function down(): void
    {
        Schema::table('buildings', function (Blueprint $table) {
            $table->dropColumn(['type', 'category', 'image', 'opening_hours', 'notes']);
        });
    }
};
