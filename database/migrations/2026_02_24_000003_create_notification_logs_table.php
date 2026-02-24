<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Tracks every push notification dispatch attempt.
     *
     * Used for:
     *  - Delivery/failure analytics  (F)
     *  - Permanent-failure auditing  (C)
     *  - Slack/external alerting     (F)
     */
    public function up(): void
    {
        Schema::create('notification_logs', function (Blueprint $table) {
            $table->id();

            // Context
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->unsignedBigInteger('event_id')->nullable()->index();
            $table->string('type', 50)->default('event_reminder')
                ->comment('Notification type: event_reminder | system | custom');

            // Content (title only — body may contain PII, omit for safety)
            $table->string('title');

            // Delivery outcome
            $table->enum('status', ['sent', 'failed', 'skipped'])->index();
            $table->unsignedSmallInteger('token_count')->default(0);
            $table->unsignedSmallInteger('success_count')->default(0);
            $table->unsignedSmallInteger('failure_count')->default(0);

            // On failure
            $table->text('failure_reason')->nullable();

            $table->timestamp('dispatched_at')->useCurrent();
            $table->timestamps();

            // Analytics queries
            $table->index(['user_id', 'status']);
            $table->index(['type', 'dispatched_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notification_logs');
    }
};
