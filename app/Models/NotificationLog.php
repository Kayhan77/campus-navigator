<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Records every push notification dispatch attempt for analytics,
 * auditing, and permanent-failure alerting.
 *
 * @property int         $id
 * @property int         $user_id
 * @property int|null    $event_id
 * @property string      $type
 * @property string      $title
 * @property string      $status        'sent' | 'failed' | 'skipped'
 * @property int         $token_count
 * @property int         $success_count
 * @property int         $failure_count
 * @property string|null $failure_reason
 * @property \Carbon\Carbon $dispatched_at
 */
class NotificationLog extends Model
{
    protected $fillable = [
        'user_id',
        'event_id',
        'type',
        'title',
        'status',
        'token_count',
        'success_count',
        'failure_count',
        'failure_reason',
        'dispatched_at',
    ];

    protected $casts = [
        'dispatched_at' => 'datetime',
    ];

    // -------------------------------------------------------------------------
    // Relationships
    // -------------------------------------------------------------------------

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    // -------------------------------------------------------------------------
    // Scopes for analytics queries
    // -------------------------------------------------------------------------

    public function scopeFailed($query)
    {
        return $query->where('status', 'failed');
    }

    public function scopeSent($query)
    {
        return $query->where('status', 'sent');
    }

    public function scopeForType($query, string $type)
    {
        return $query->where('type', $type);
    }

    // -------------------------------------------------------------------------
    // Computed
    // -------------------------------------------------------------------------

    /**
     * Delivery success rate as a float 0.0–1.0.
     */
    public function getDeliveryRateAttribute(): float
    {
        if ($this->token_count === 0) {
            return 0.0;
        }

        return round($this->success_count / $this->token_count, 4);
    }
}
