<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Drop obsolete signed-URL verification table
        Schema::dropIfExists('email_verifications');

        // Remove verification_code from users — OTP lives in pending_registrations only
        if (Schema::hasColumn('users', 'verification_code')) {
            Schema::table('users', function (Blueprint $table) {
                $table->dropColumn('verification_code');
            });
        }
    }

    public function down(): void
    {
        // Restore email_verifications table
        Schema::create('email_verifications', function (Blueprint $table) {
            $table->id();
            $table->string('email')->unique();
            $table->string('token')->unique();
            $table->timestamp('expires_at');
            $table->timestamps();
        });

        // Restore verification_code column on users
        Schema::table('users', function (Blueprint $table) {
            $table->string('verification_code')->nullable()->after('role');
        });
    }
};
