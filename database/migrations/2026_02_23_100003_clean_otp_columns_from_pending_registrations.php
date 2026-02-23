<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pending_registrations', function (Blueprint $table) {
            $table->dropColumn(['verification_code', 'expires_at', 'last_sent_at']);
        });
    }

    public function down(): void
    {
        Schema::table('pending_registrations', function (Blueprint $table) {
            $table->string('verification_code')->nullable()->after('token');
            $table->timestamp('expires_at')->nullable()->after('verification_code');
            $table->timestamp('last_sent_at')->nullable()->after('expires_at');
        });
    }
};
